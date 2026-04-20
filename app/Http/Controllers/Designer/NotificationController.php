<?php

namespace App\Http\Controllers\Designer;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    private const REFERRAL_CONFIRM_ACTION = 'confirm_referral_supplier';

    public function index(Request $request)
    {
        $notifications = UserNotification::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('designer.notifications.index', [
            'notifications' => $notifications,
        ]);
    }

    public function markRead(Request $request, int $notificationId): RedirectResponse
    {
        UserNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereKey($notificationId)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return redirect()->back()->with('status', __('notifications.marked_read'));
    }

    public function destroy(Request $request, int $notificationId): RedirectResponse
    {
        UserNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereKey($notificationId)
            ->delete();

        return redirect()->back()->with('status', __('notifications.deleted'));
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        UserNotification::query()
            ->where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return redirect()->back()->with('status', __('notifications.marked_all_read'));
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = UserNotification::query()
            ->where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
    }

    public function confirmReferralSupplier(Request $request, int $notificationId): RedirectResponse
    {
        $notification = UserNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereKey($notificationId)
            ->firstOrFail();

        if ($notification->action_key !== self::REFERRAL_CONFIRM_ACTION || (int) $notification->related_supplier_id < 1) {
            return redirect()->back()->with('status', __('notifications.action_unavailable'));
        }

        $supplier = Supplier::query()
            ->where('created_by_user_id', $request->user()->id)
            ->whereKey((int) $notification->related_supplier_id)
            ->first();

        if (! $supplier) {
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

        return redirect()->back()->with('status', __('notifications.referral_supplier_confirmed'));
    }
}


