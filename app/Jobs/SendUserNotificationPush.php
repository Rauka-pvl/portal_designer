<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\UserNotification;
use App\Services\Push\ExpoPushService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class SendUserNotificationPush implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $notificationId) {}

    public function handle(ExpoPushService $push): void
    {
        $notification = UserNotification::query()->find($this->notificationId);
        if (! $notification || (int) $notification->user_id <= 0) {
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
            $title = __('notifications.title');
        }

        if ($body === '') {
            $body = $title;
        }

        $data = array_filter([
            'type' => $notification->action_key ?: 'notification',
            'id' => (string) $notification->id,
            'related_order_id' => $notification->related_order_id !== null
                ? (string) $notification->related_order_id
                : null,
            'related_supplier_id' => $notification->related_supplier_id !== null
                ? (string) $notification->related_supplier_id
                : null,
            'related_post_id' => $notification->related_post_id !== null
                ? (string) $notification->related_post_id
                : null,
            'related_invitation_id' => $notification->related_invitation_id !== null
                ? (string) $notification->related_invitation_id
                : null,
        ], fn ($value) => $value !== null && $value !== '');

        $push->sendToUser(
            $user,
            Str::limit($title, 80, ''),
            Str::limit($body, 200, ''),
            $data,
        );
    }
}
