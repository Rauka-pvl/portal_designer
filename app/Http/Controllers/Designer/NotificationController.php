<?php

namespace App\Http\Controllers\Designer;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\UserNotification;
use App\Support\NotificationActions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    private const REFERRAL_CONFIRM_ACTION = 'confirm_referral_supplier';

    public function index(Request $request): View
    {
        $filter = in_array($request->query('filter'), ['all', 'unread'], true)
            ? $request->query('filter')
            : 'all';

        $query = UserNotification::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id');

        if ($filter === 'unread') {
            $query->where('is_read', false);
        }

        $notifications = $query->paginate(20)->withQueryString();

        $unreadTotal = UserNotification::query()
            ->where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        $actionsById = [];
        foreach ($notifications as $notification) {
            $actionsById[$notification->id] = NotificationActions::resolve($notification, $request->user());
        }

        return view('notifications.index', array_merge($this->viewData($request), [
            'notifications' => $notifications,
            'actionsById' => $actionsById,
            'filter' => $filter,
            'unreadTotal' => $unreadTotal,
        ]));
    }

    public function markRead(Request $request, int $notificationId): JsonResponse|RedirectResponse
    {
        $notification = $this->ownedNotification($request, $notificationId);
        $notification->is_read = true;
        $notification->read_at = now();
        $notification->save();

        return $this->mutationResponse($request, __('notifications.marked_read'), $notification);
    }

    public function markUnread(Request $request, int $notificationId): JsonResponse|RedirectResponse
    {
        $notification = $this->ownedNotification($request, $notificationId);
        $notification->is_read = false;
        $notification->read_at = null;
        $notification->save();

        return $this->mutationResponse($request, __('notifications.marked_unread'), $notification);
    }

    public function destroy(Request $request, int $notificationId): JsonResponse|RedirectResponse
    {
        $notification = $this->ownedNotification($request, $notificationId);
        $notification->delete();

        if ($this->wantsJson($request)) {
            return response()->json([
                'ok' => true,
                'message' => __('notifications.deleted'),
                'unread_count' => $this->unreadCountValue($request),
                'deleted_id' => $notificationId,
            ]);
        }

        return redirect()->back()->with('status', __('notifications.deleted'));
    }

    public function markAllRead(Request $request): JsonResponse|RedirectResponse
    {
        UserNotification::query()
            ->where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        if ($this->wantsJson($request)) {
            return response()->json([
                'ok' => true,
                'message' => __('notifications.marked_all_read'),
                'unread_count' => 0,
            ]);
        }

        return redirect()->back()->with('status', __('notifications.marked_all_read'));
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json(['count' => $this->unreadCountValue($request)]);
    }

    public function confirmReferralSupplier(Request $request, int $notificationId): JsonResponse|RedirectResponse
    {
        if (($request->user()->role ?? '') !== 'designer') {
            abort(403);
        }

        $notification = $this->ownedNotification($request, $notificationId);

        if ($notification->action_key !== self::REFERRAL_CONFIRM_ACTION || (int) $notification->related_supplier_id < 1) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'ok' => false,
                    'message' => __('notifications.action_unavailable'),
                ], 422);
            }

            return redirect()->back()->with('status', __('notifications.action_unavailable'));
        }

        $supplier = Supplier::query()
            ->where('created_by_user_id', $request->user()->id)
            ->whereKey((int) $notification->related_supplier_id)
            ->first();

        if (! $supplier) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'ok' => false,
                    'message' => __('notifications.supplier_missing'),
                ], 404);
            }

            return redirect()->back()->with('status', __('notifications.supplier_missing'));
        }

        $supplier->is_confirmed_by_designer = true;
        $supplier->is_referral_submitted = true;
        $supplier->moderation_status = 'pending';
        $supplier->moderation_comment = null;
        $supplier->moderation_reviewer_id = null;
        $supplier->moderation_reviewed_at = null;
        $supplier->save();

        $notification->is_read = true;
        $notification->read_at = now();
        $notification->action_key = null;
        $notification->save();

        $actions = NotificationActions::resolve($notification->fresh(), $request->user());

        if ($this->wantsJson($request)) {
            return response()->json([
                'ok' => true,
                'message' => __('notifications.referral_supplier_confirmed'),
                'unread_count' => $this->unreadCountValue($request),
                'notification' => [
                    'id' => $notification->id,
                    'is_read' => true,
                    'action_key' => null,
                    'actions' => $actions,
                ],
            ]);
        }

        return redirect()->back()->with('status', __('notifications.referral_supplier_confirmed'));
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(Request $request): array
    {
        $isSupplier = ($request->user()->role ?? '') === 'supplier';

        return [
            'layout' => $isSupplier ? 'layouts.supplier' : 'layouts.dashboard',
            'routePrefix' => $isSupplier ? 'supplier.notifications' : 'notifications',
            'isDesigner' => ! $isSupplier,
            'reviewStoreUrl' => $isSupplier ? route('supplier.reviews.store') : route('reviews.store'),
            'subtitle' => $isSupplier
                ? __('notifications.subtitle_supplier')
                : __('notifications.subtitle'),
        ];
    }

    private function ownedNotification(Request $request, int $notificationId): UserNotification
    {
        return UserNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereKey($notificationId)
            ->firstOrFail();
    }

    private function unreadCountValue(Request $request): int
    {
        return (int) UserNotification::query()
            ->where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();
    }

    private function wantsJson(Request $request): bool
    {
        return $request->expectsJson() || $request->ajax() || $request->wantsJson();
    }

    private function mutationResponse(Request $request, string $message, UserNotification $notification): JsonResponse|RedirectResponse
    {
        if ($this->wantsJson($request)) {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'unread_count' => $this->unreadCountValue($request),
                'notification' => [
                    'id' => $notification->id,
                    'is_read' => (bool) $notification->is_read,
                    'action_key' => $notification->action_key,
                    'actions' => NotificationActions::resolve($notification, $request->user()),
                ],
            ]);
        }

        return redirect()->back()->with('status', $message);
    }
}
