<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\UserNotification;
use App\Support\NotificationActions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class NotificationController extends Controller
{
    private const REFERRAL_CONFIRM_ACTION = 'confirm_referral_supplier';

    /**
     * GET /api/notifications?filter=all|unread&page=1
     */
    public function index(Request $request): JsonResponse
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

        $paginator = $query->paginate(min(50, max(1, (int) $request->query('per_page', 20))));

        $items = $paginator->getCollection()->map(function (UserNotification $notification) use ($request) {
            return $this->payload($notification, $request);
        })->values();

        return response()->json([
            'success' => true,
            'filter' => $filter,
            'unread_count' => $this->unreadCountValue($request),
            'notifications' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * POST /api/notifications
     * Body: { "action": "mark_all_read" }
     *    or { "action": "mark_read"|"mark_unread"|"delete", "ids": [1,2] }
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['mark_all_read', 'mark_read', 'mark_unread', 'delete'])],
            'ids' => ['required_unless:action,mark_all_read', 'array', 'min:1'],
            'ids.*' => ['integer', 'min:1'],
        ]);

        $userId = (int) $request->user()->id;

        if ($data['action'] === 'mark_all_read') {
            UserNotification::query()
                ->where('user_id', $userId)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => __('notifications.marked_all_read'),
                'unread_count' => 0,
            ]);
        }

        $ids = array_values(array_unique(array_map('intval', $data['ids'] ?? [])));
        $query = UserNotification::query()
            ->where('user_id', $userId)
            ->whereIn('id', $ids);

        $affected = match ($data['action']) {
            'mark_read' => $query->update(['is_read' => true, 'read_at' => now()]),
            'mark_unread' => $query->update(['is_read' => false, 'read_at' => null]),
            'delete' => $query->delete(),
            default => 0,
        };

        $message = match ($data['action']) {
            'mark_read' => __('notifications.marked_read'),
            'mark_unread' => __('notifications.marked_unread'),
            'delete' => __('notifications.deleted'),
            default => 'ok',
        };

        return response()->json([
            'success' => true,
            'message' => $message,
            'affected' => (int) $affected,
            'unread_count' => $this->unreadCountValue($request),
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'count' => $this->unreadCountValue($request),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        UserNotification::query()
            ->where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => __('notifications.marked_all_read'),
            'unread_count' => 0,
        ]);
    }

    public function markRead(Request $request, int $id): JsonResponse
    {
        $notification = $this->owned($request, $id);
        $notification->is_read = true;
        $notification->read_at = now();
        $notification->save();

        return response()->json([
            'success' => true,
            'message' => __('notifications.marked_read'),
            'unread_count' => $this->unreadCountValue($request),
            'notification' => $this->payload($notification->fresh(), $request),
        ]);
    }

    public function markUnread(Request $request, int $id): JsonResponse
    {
        $notification = $this->owned($request, $id);
        $notification->is_read = false;
        $notification->read_at = null;
        $notification->save();

        return response()->json([
            'success' => true,
            'message' => __('notifications.marked_unread'),
            'unread_count' => $this->unreadCountValue($request),
            'notification' => $this->payload($notification->fresh(), $request),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->owned($request, $id)->delete();

        return response()->json([
            'success' => true,
            'message' => __('notifications.deleted'),
            'unread_count' => $this->unreadCountValue($request),
            'deleted_id' => $id,
        ]);
    }

    public function confirmReferralSupplier(Request $request, int $id): JsonResponse
    {
        if (($request->user()->role ?? '') !== 'designer') {
            abort(403);
        }

        $notification = $this->owned($request, $id);

        if ($notification->action_key !== self::REFERRAL_CONFIRM_ACTION || (int) $notification->related_supplier_id < 1) {
            throw ValidationException::withMessages([
                'notification' => [__('notifications.action_unavailable')],
            ]);
        }

        $supplier = Supplier::query()
            ->where('created_by_user_id', $request->user()->id)
            ->whereKey((int) $notification->related_supplier_id)
            ->first();

        if (! $supplier) {
            return response()->json([
                'success' => false,
                'message' => __('notifications.supplier_missing'),
            ], 404);
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

        return response()->json([
            'success' => true,
            'message' => __('notifications.referral_supplier_confirmed'),
            'unread_count' => $this->unreadCountValue($request),
            'notification' => $this->payload($notification->fresh(), $request),
        ]);
    }

    private function owned(Request $request, int $id): UserNotification
    {
        return UserNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereKey($id)
            ->firstOrFail();
    }

    private function unreadCountValue(Request $request): int
    {
        return (int) UserNotification::query()
            ->where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(UserNotification $notification, Request $request): array
    {
        return [
            'id' => $notification->id,
            'title' => $notification->title,
            'comment' => $notification->comment,
            'is_read' => (bool) $notification->is_read,
            'read_at' => optional($notification->read_at)?->toIso8601String(),
            'created_at' => optional($notification->created_at)?->toIso8601String(),
            'action_key' => $notification->action_key,
            'related_supplier_id' => $notification->related_supplier_id,
            'related_order_id' => $notification->related_order_id,
            'related_post_id' => $notification->related_post_id,
            'actions' => NotificationActions::resolve($notification, $request->user()),
        ];
    }
}
