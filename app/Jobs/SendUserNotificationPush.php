<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\UserNotification;
use App\Services\Push\ExpoPushService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendUserNotificationPush implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $notificationId) {}

    public function handle(ExpoPushService $push): void
    {
        $notification = UserNotification::query()->find($this->notificationId);
        if (! $notification) {
            return;
        }

        $user = User::query()->find($notification->user_id);
        if (! $user) {
            return;
        }

        $title = trim((string) ($notification->title ?? ''));
        $body = trim((string) ($notification->comment ?? ''));

        if ($title === '' && $body === '') {
            return;
        }

        if ($title === '') {
            $title = 'Portal Designer';
        }

        if ($body === '') {
            $body = $title;
        }

        $data = array_filter([
            'type' => $notification->action_key ?: 'notification',
            'id' => (string) $notification->id,
            'notification_id' => (string) $notification->id,
            'related_order_id' => $notification->related_order_id !== null
                ? (string) $notification->related_order_id
                : null,
            'related_post_id' => $notification->related_post_id !== null
                ? (string) $notification->related_post_id
                : null,
            'related_supplier_id' => $notification->related_supplier_id !== null
                ? (string) $notification->related_supplier_id
                : null,
            'related_invitation_id' => $notification->related_invitation_id !== null
                ? (string) $notification->related_invitation_id
                : null,
            'action_key' => $notification->action_key,
        ], fn ($value) => $value !== null && $value !== '');

        try {
            $push->sendToUser($user, $title, $body, $data);
        } catch (\Throwable $e) {
            Log::warning('push.notification_failed', [
                'notification_id' => $notification->id,
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
