<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiAccountEndpointsTest extends TestCase
{
    use RefreshDatabase;

    private function designer(): User
    {
        return User::factory()->create([
            'role' => 'designer',
            'name' => 'Designer API',
            'email' => 'designer-api@example.com',
            'password' => Hash::make('OldPassword1!'),
            'subscription_trial_ends_at' => now()->addDays(7),
        ]);
    }

    public function test_get_and_post_notifications(): void
    {
        $user = $this->designer();
        $n1 = UserNotification::query()->create([
            'user_id' => $user->id,
            'title' => 'Hello',
            'comment' => 'Body',
            'is_read' => false,
        ]);
        UserNotification::query()->create([
            'user_id' => $user->id,
            'title' => 'Other',
            'comment' => 'x',
            'is_read' => true,
        ]);

        Sanctum::actingAs($user);

        $list = $this->getJson('/api/notifications?filter=unread');
        $list->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('unread_count', 1)
            ->assertJsonCount(1, 'notifications');

        $this->postJson('/api/notifications', [
            'action' => 'mark_read',
            'ids' => [$n1->id],
        ])->assertOk()->assertJsonPath('unread_count', 0);

        $this->assertTrue((bool) $n1->fresh()->is_read);

        $this->postJson('/api/notifications', [
            'action' => 'mark_all_read',
        ])->assertOk()->assertJsonPath('unread_count', 0);
    }

    public function test_update_me_profile_and_password(): void
    {
        $user = $this->designer();
        Sanctum::actingAs($user);

        $this->putJson('/api/me/profile', [
            'name' => 'New Name',
            'email' => 'designer-api@example.com',
            'phone' => '+77001112233',
            'city' => 'Almaty',
            'short_description' => 'Interior',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.name', 'New Name')
            ->assertJsonPath('user.profile.city', 'Almaty');

        $this->postJson('/api/me/password', [
            'current_password' => 'OldPassword1!',
            'password' => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('NewPassword1!', $user->fresh()->password));
    }

    public function test_forgot_password_returns_generic_success(): void
    {
        $this->designer();

        $this->postJson('/api/forgot-password', [
            'email' => 'designer-api@example.com',
        ])->assertOk()->assertJsonPath('success', true);

        // Unknown email — same shape (no leak)
        $this->postJson('/api/forgot-password', [
            'email' => 'nobody@example.com',
        ])->assertOk()->assertJsonPath('success', true);
    }

    public function test_reset_password_with_token(): void
    {
        $user = $this->designer();
        $token = Password::broker()->createToken($user);

        $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'ResetPassword1!',
            'password_confirmation' => 'ResetPassword1!',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('ResetPassword1!', $user->fresh()->password));
    }

    public function test_notifications_are_scoped_to_owner(): void
    {
        $owner = $this->designer();
        $other = User::factory()->create([
            'role' => 'designer',
            'subscription_trial_ends_at' => now()->addDays(7),
        ]);
        $n = UserNotification::query()->create([
            'user_id' => $owner->id,
            'title' => 'Secret',
            'comment' => 'x',
            'is_read' => false,
        ]);

        Sanctum::actingAs($other);
        $this->postJson('/api/notifications/'.$n->id.'/read')->assertNotFound();
    }
}
