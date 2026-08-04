<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UserNotificationPushTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_notification_sends_expo_push(): void
    {
        Http::fake([
            'exp.host/*' => Http::response([
                'data' => [
                    ['status' => 'ok', 'id' => 'ticket-1'],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();
        Device::query()->create([
            'user_id' => $user->id,
            'token' => 'ExponentPushToken[notify-me]',
            'platform' => 'ios',
            'provider' => 'expo',
            'app' => 'mobile',
        ]);

        $notification = UserNotification::query()->create([
            'user_id' => $user->id,
            'title' => 'Новое сообщение',
            'comment' => 'Салам',
            'is_read' => false,
            'action_key' => 'chat',
        ]);

        Http::assertSent(function ($request) use ($notification) {
            $payload = $request->data();
            $first = isset($payload[0]) ? $payload[0] : $payload;

            return $request->url() === 'https://exp.host/--/api/v2/push/send'
                && ($first['to'] ?? null) === 'ExponentPushToken[notify-me]'
                && ($first['title'] ?? null) === 'Новое сообщение'
                && ($first['body'] ?? null) === 'Салам'
                && ($first['data']['type'] ?? null) === 'chat'
                && ($first['data']['id'] ?? null) === (string) $notification->id;
        });
    }

    public function test_creating_notification_without_devices_does_not_call_expo(): void
    {
        Http::fake();

        $user = User::factory()->create();

        UserNotification::query()->create([
            'user_id' => $user->id,
            'title' => 'Test',
            'comment' => 'No device',
            'is_read' => false,
        ]);

        Http::assertNothingSent();
    }
}
