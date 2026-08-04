<?php

namespace Tests\Feature;

use App\Jobs\SendUserNotificationPush;
use App\Models\Device;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Push\ExpoPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DevicePushApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_device_requires_auth(): void
    {
        $this->postJson('/api/devices', [
            'token' => 'ExponentPushToken[abc]',
            'platform' => 'ios',
            'provider' => 'expo',
            'app' => 'mobile',
        ])->assertUnauthorized();
    }

    public function test_register_device_and_push_tokens_alias(): void
    {
        $user = User::factory()->create(['account_type' => 'designer']);
        Sanctum::actingAs($user);

        $payload = [
            'token' => 'ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]',
            'platform' => 'ios',
            'provider' => 'expo',
            'app' => 'mobile',
        ];

        $this->postJson('/api/devices', $payload)
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('device.token', $payload['token'])
            ->assertJsonPath('device.platform', 'ios');

        $this->assertDatabaseHas('devices', [
            'user_id' => $user->id,
            'token' => $payload['token'],
            'provider' => 'expo',
            'app' => 'mobile',
        ]);

        // Idempotent upsert via fallback route
        $this->postJson('/api/push-tokens', array_merge($payload, ['platform' => 'android']))
            ->assertOk()
            ->assertJsonPath('device.platform', 'android');

        $this->assertSame(1, Device::query()->where('token', $payload['token'])->count());
    }

    public function test_token_moves_to_new_user_on_re_register(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $token = 'ExponentPushToken[shared-device]';

        Device::query()->create([
            'user_id' => $first->id,
            'token' => $token,
            'platform' => 'ios',
            'provider' => 'expo',
            'app' => 'mobile',
        ]);

        Sanctum::actingAs($second);
        $this->postJson('/api/devices', [
            'token' => $token,
            'platform' => 'ios',
            'provider' => 'expo',
            'app' => 'mobile',
        ])->assertOk();

        $this->assertDatabaseHas('devices', [
            'user_id' => $second->id,
            'token' => $token,
        ]);
        $this->assertDatabaseMissing('devices', [
            'user_id' => $first->id,
            'token' => $token,
        ]);
    }

    public function test_delete_device_on_logout_flow(): void
    {
        $user = User::factory()->create();
        $token = 'ExponentPushToken[to-delete]';

        Device::query()->create([
            'user_id' => $user->id,
            'token' => $token,
            'provider' => 'expo',
            'app' => 'mobile',
        ]);

        Sanctum::actingAs($user);

        $this->deleteJson('/api/devices', ['token' => $token])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('deleted', true);

        $this->assertDatabaseMissing('devices', ['token' => $token]);

        $this->deleteJson('/api/push-tokens', ['token' => $token])
            ->assertOk()
            ->assertJsonPath('deleted', false);
    }

    public function test_expo_push_service_sends_and_prunes_invalid_tokens(): void
    {
        Http::fake([
            'exp.host/*' => Http::response([
                'data' => [
                    [
                        'status' => 'error',
                        'message' => '"ExponentPushToken[dead]" is not a registered push notification recipient',
                        'details' => ['error' => 'DeviceNotRegistered'],
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();
        Device::query()->create([
            'user_id' => $user->id,
            'token' => 'ExponentPushToken[dead]',
            'provider' => 'expo',
            'app' => 'mobile',
        ]);

        $result = app(ExpoPushService::class)->sendToUser(
            $user,
            'Новое сообщение',
            'Салам',
            ['type' => 'chat', 'id' => '7']
        );

        $this->assertFalse($result['ok']);
        $this->assertDatabaseMissing('devices', ['token' => 'ExponentPushToken[dead]']);

        Http::assertSent(function ($request) {
            $body = $request->data();
            $first = $body[0] ?? $body;

            return $request->url() === 'https://exp.host/--/api/v2/push/send'
                && ($first['to'] ?? null) === 'ExponentPushToken[dead]'
                && ($first['title'] ?? null) === 'Новое сообщение'
                && ($first['body'] ?? null) === 'Салам'
                && ($first['data']['type'] ?? null) === 'chat';
        });
    }

    public function test_creating_user_notification_dispatches_push_job(): void
    {
        Bus::fake([SendUserNotificationPush::class]);

        $user = User::factory()->create();

        $notification = UserNotification::query()->create([
            'user_id' => $user->id,
            'title' => 'Новое сообщение',
            'comment' => 'Салам',
            'is_read' => false,
            'action_key' => 'order_offer',
        ]);

        Bus::assertDispatched(SendUserNotificationPush::class, function (SendUserNotificationPush $job) use ($notification) {
            return $job->notificationId === (int) $notification->id;
        });
    }

    public function test_push_job_sends_expo_payload_from_notification(): void
    {
        Http::fake([
            'exp.host/*' => Http::response([
                'data' => [['status' => 'ok', 'id' => 'ticket-1']],
            ], 200),
        ]);

        $user = User::factory()->create();
        Device::query()->create([
            'user_id' => $user->id,
            'token' => 'ExponentPushToken[live]',
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

        // QUEUE_CONNECTION=sync in phpunit → observer job runs immediately.
        Http::assertSent(function ($request) use ($notification) {
            $body = $request->data();
            $first = is_array($body) && array_is_list($body) ? ($body[0] ?? []) : $body;

            return ($first['to'] ?? null) === 'ExponentPushToken[live]'
                && ($first['title'] ?? null) === 'Новое сообщение'
                && ($first['body'] ?? null) === 'Салам'
                && ($first['data']['type'] ?? null) === 'chat'
                && ($first['data']['id'] ?? null) === (string) $notification->id;
        });
    }
}
