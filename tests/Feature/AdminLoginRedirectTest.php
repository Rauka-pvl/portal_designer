<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_admin_role_accessor_is_moderator_for_routing(): void
    {
        $admin = User::factory()->create(['account_type' => 'system_admin']);

        $this->assertSame('system_admin', $admin->account_type);
        $this->assertSame('moderator', $admin->role);
        $this->assertTrue($admin->isSystemAdmin());
    }

    public function test_system_admin_can_login_via_designer_portal_and_reaches_moderator(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'account_type' => 'system_admin',
            'account_status' => 'active',
        ]);

        $this->post(route('login'), [
            'email' => 'admin@example.com',
            'password' => 'password',
            'portal' => 'designer',
        ])->assertRedirect(route('moderator.index'));

        $this->assertAuthenticatedAs($admin);

        $this->get(route('moderator.index'))->assertOk();
        $this->get('/')->assertRedirect(route('moderator.index'));
    }

    public function test_system_admin_is_not_rejected_as_wrong_portal(): void
    {
        User::factory()->create([
            'email' => 'admin2@example.com',
            'password' => bcrypt('password'),
            'account_type' => 'system_admin',
        ]);

        $this->from(route('login'))
            ->post(route('login'), [
                'email' => 'admin2@example.com',
                'password' => 'password',
                'portal' => 'designer',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('moderator.index'));
    }
}
