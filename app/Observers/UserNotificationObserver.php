<?php

namespace App\Observers;

use App\Jobs\SendUserNotificationPush;
use App\Models\UserNotification;

class UserNotificationObserver
{
    public function created(UserNotification $notification): void
    {
        if ((int) $notification->user_id <= 0) {
            return;
        }

        SendUserNotificationPush::dispatch((int) $notification->id);
    }
}
