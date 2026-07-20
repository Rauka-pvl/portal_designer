<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DesignerApiCrudTest extends TestCase
{
    use RefreshDatabase;

    private function designer(): User
    {
        return User::factory()->create([
            'role' => 'designer',
            'subscription_trial_ends_at' => now()->addDays(7),
        ]);
    }

    public function test_api_can_create_update_and_delete_client(): void
    {
        $user = $this->designer();
        Sanctum::actingAs($user);

        $create = $this->postJson('/api/clients', [
            'full_name' => 'Иван Тестов',
            'client_type' => 'person',
            'phone' => '+77001112233',
            'email' => 'ivan@example.com',
            'status' => 'new',
            'comment' => 'API client',
        ]);

        $create->assertOk()->assertJsonPath('success', true);
        $id = (int) ($create->json('client.id') ?? Client::query()->where('user_id', $user->id)->value('id'));
        $this->assertGreaterThan(0, $id);

        $update = $this->putJson('/api/clients/'.$id, [
            'full_name' => 'Иван Обновлённый',
            'client_type' => 'person',
            'phone' => '+77001112233',
            'email' => 'ivan@example.com',
            'status' => 'in_work',
        ]);

        $update->assertOk()->assertJsonPath('success', true);
        $this->assertSame('Иван Обновлённый', Client::query()->find($id)?->full_name);
        $this->assertSame('in_work', Client::query()->find($id)?->status);

        $this->deleteJson('/api/clients/'.$id)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNull(Client::query()->find($id));
    }

    public function test_supplier_cannot_use_designer_write_api(): void
    {
        $user = User::factory()->create([
            'role' => 'supplier',
            'must_change_password' => false,
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/clients', [
            'full_name' => 'Nope',
            'client_type' => 'person',
            'phone' => '+77001112233',
            'email' => 'nope@example.com',
            'status' => 'new',
        ])->assertForbidden();
    }
}
