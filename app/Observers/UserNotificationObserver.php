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

        // In tests RefreshDatabase wraps DB in a transaction; afterCommit never fires mid-test.
        if (app()->runningUnitTests()) {
            SendUserNotificationPush::dispatch($notification->id);

            return;
        }

        SendUserNotificationPush::dispatch($notification->id)->afterCommit();
    }
}
