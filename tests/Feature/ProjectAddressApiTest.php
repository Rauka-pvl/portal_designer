<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use App\Services\Crm\PipelineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectAddressApiTest extends TestCase
{
    use RefreshDatabase;

    private function designer(): User
    {
        $user = User::factory()->create([
            'account_type' => 'designer',
            'subscription_trial_ends_at' => now()->addDays(14),
        ]);
        app(PipelineService::class)->ensureDefaultsForUser((int) $user->id);

        return $user;
    }

    public function test_api_creates_project_with_address_on_projects_table(): void
    {
        $user = $this->designer();
        Sanctum::actingAs($user);

        $client = Client::query()->create([
            'user_id' => $user->id,
            'full_name' => 'Client Map',
            'client_type' => 'person',
            'phone' => '+77001112233',
            'status' => 'new',
        ]);

        $response = $this->postJson('/api/projects', [
            'name' => 'API Address Project',
            'client_id' => $client->id,
            'city' => 'Алматы',
            'object_type' => 'apartment',
            'object_address' => 'пр. Абая, 10',
            'apartment' => '12',
            'apartment_floor' => '3',
            'apartment_entrance' => '1',
            'area' => 72.5,
            'latitude' => 43.238949,
            'longitude' => 76.945465,
            'repair_budget_planned' => 1000000,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'API Address Project')
            ->assertJsonPath('data.city', 'Алматы')
            ->assertJsonPath('data.object_address', 'пр. Абая, 10')
            ->assertJsonPath('data.object_type', 'apartment')
            ->assertJsonPath('data.property.object_address', 'пр. Абая, 10')
            ->assertJsonPath('data.property.object_type', 'apartment')
            ->assertJsonPath('data.latitude', '43.238949')
            ->assertJsonPath('data.longitude', '76.945465');

        $this->assertNotNull($response->json('data.stage_id'));

        $this->assertDatabaseHas('projects', [
            'name' => 'API Address Project',
            'city' => 'Алматы',
            'address' => 'пр. Абая, 10',
            'object_type' => 'apartment',
            'object_id' => null,
        ]);
    }

    public function test_api_rejects_address_without_coordinates(): void
    {
        $user = $this->designer();
        Sanctum::actingAs($user);

        $this->postJson('/api/projects', [
            'name' => 'No coords',
            'object_address' => 'ул. Без координат',
        ])->assertStatus(422);
    }

    public function test_objects_write_endpoints_return_gone(): void
    {
        Sanctum::actingAs($this->designer());

        $this->postJson('/api/objects', ['address' => 'x'])->assertStatus(410)
            ->assertJsonPath('code', 'objects_retired');
        $this->putJson('/api/objects/1', ['address' => 'x'])->assertStatus(410);
        $this->deleteJson('/api/objects/1')->assertStatus(410);
    }

    public function test_accepts_address_alias_fields(): void
    {
        $user = $this->designer();
        Sanctum::actingAs($user);

        $this->postJson('/api/projects', [
            'name' => 'Alias fields',
            'city' => 'Астана',
            'type' => 'house',
            'address' => 'ул. Кабанбай батыра 1',
            'latitude' => 51.1694,
            'longitude' => 71.4491,
        ])->assertCreated()
            ->assertJsonPath('data.object_type', 'house')
            ->assertJsonPath('data.object_address', 'ул. Кабанбай батыра 1');
    }
}
