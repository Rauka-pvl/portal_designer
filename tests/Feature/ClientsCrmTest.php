<?php

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use App\Services\Crm\PipelineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientsCrmTest extends TestCase
{
    use RefreshDatabase;

    private function designer(): User
    {
        return User::factory()->create([
            'role' => 'designer',
            'subscription_trial_ends_at' => now()->addDays(14),
        ]);
    }

    public function test_clients_crm_page_loads_with_modes_and_modals(): void
    {
        $user = $this->designer();

        $html = $this->actingAs($user)->get('/clients')->assertOk()->getContent();

        $this->assertStringContainsString('crm-clients-workspace', $html);
        $this->assertStringContainsString('clients-list-panel', $html);
        $this->assertStringContainsString('clients-kanban-panel', $html);
        $this->assertStringNotContainsString('clients-cards-panel', $html);
        $this->assertStringNotContainsString('clients-funnel-panel', $html);
        $this->assertStringContainsString('client-form-modal', $html);
        $this->assertStringContainsString('client-detail-modal', $html);
        $this->assertStringContainsString('crm.clients.view', $html);
        $this->assertStringContainsString(__('clients.create_client'), $html);
        $this->assertStringContainsString(__('clients.kanban'), $html);
        $this->assertStringContainsString(__('clients.list'), $html);
        $this->assertStringContainsString('pipeline-settings-btn', $html);
        $this->assertStringContainsString('CrmPipelineSettingsConfig', $html);
        $this->assertStringContainsString('pipelineType: \'client\'', $html);
        $this->assertStringNotContainsString('tab-btn', $html);
    }

    public function test_can_create_person_and_company_clients_via_json(): void
    {
        $user = $this->designer();

        $person = $this->actingAs($user)->postJson('/clients/add', [
            'full_name' => 'Иван Иванов',
            'client_type' => 'person',
            'phone' => '+77001112233',
            'email' => 'ivan@example.com',
            'status' => 'new',
            'comment' => 'note',
            'link' => 'https://example.com',
        ])->assertOk()->json();

        $this->assertTrue($person['success']);
        $this->assertSame('person', $person['client']['client_type']);
        $this->assertArrayHasKey('projects_count', $person['client']);

        $company = $this->actingAs($user)->postJson('/clients/add', [
            'full_name' => 'ТОО Дизайн',
            'client_type' => 'company',
            'phone' => '+77004445566',
            'email' => 'company@example.com',
            'status' => 'in_work',
        ])->assertOk()->json('client');

        $this->assertSame('company', $company['client_type']);
        $this->assertSame('ТОО Дизайн', $company['full_name']);
    }

    public function test_validation_error_does_not_create_client(): void
    {
        $user = $this->designer();

        $this->actingAs($user)->postJson('/clients/add', [
            'full_name' => '',
            'client_type' => 'person',
            'phone' => '',
            'email' => 'bad',
            'status' => 'new',
        ])->assertStatus(422);

        $this->assertSame(0, Client::query()->where('user_id', $user->id)->count());
    }

    public function test_search_and_status_filter_work(): void
    {
        $user = $this->designer();
        Client::query()->create([
            'user_id' => $user->id,
            'full_name' => 'Alpha Client',
            'client_type' => 'person',
            'phone' => '+77001110000',
            'email' => 'alpha@example.com',
            'status' => 'new',
        ]);
        Client::query()->create([
            'user_id' => $user->id,
            'full_name' => 'Beta Client',
            'client_type' => 'company',
            'phone' => '+77002220000',
            'email' => 'beta@example.com',
            'status' => 'in_work',
        ]);

        $search = $this->actingAs($user)->getJson('/clients/search?search=Alpha')->assertOk()->json('data');
        $this->assertCount(1, $search);
        $this->assertSame('Alpha Client', $search[0]['full_name']);

        $filtered = $this->actingAs($user)->getJson('/clients/search?status=in_work')->assertOk()->json('data');
        $this->assertCount(1, $filtered);
        $this->assertSame('Beta Client', $filtered[0]['full_name']);
    }

    public function test_status_update_and_show_json_with_projects(): void
    {
        $user = $this->designer();
        app(PipelineService::class)->ensureDefaultsForUser((int) $user->id);

        $client = Client::query()->create([
            'user_id' => $user->id,
            'full_name' => 'With Project',
            'client_type' => 'person',
            'phone' => '+77003330000',
            'email' => 'proj@example.com',
            'status' => 'new',
        ]);

        Project::query()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'name' => 'Клиентский проект',
            'status' => ProjectStatus::ContractNegotiation->value,
            'start_date' => now()->toDateString(),
            'planned_end_date' => now()->addMonth()->toDateString(),
            'planned_cost' => 150000,
            'actual_cost' => 0,
            'moderation_status' => 'approved',
        ]);

        $this->actingAs($user)->patchJson('/clients/'.$client->id.'/status', [
            'status' => 'in_work',
        ])->assertOk()->assertJsonPath('client.status', 'in_work');

        $show = $this->actingAs($user)->getJson('/clients/'.$client->id)->assertOk()->json();
        $this->assertTrue($show['success']);
        $this->assertSame('With Project', $show['client']['full_name']);
        $this->assertCount(1, $show['projects']);
        $this->assertSame('Клиентский проект', $show['projects'][0]['name']);
        $this->assertSame(1, (int) $show['client']['projects_count']);
    }

    public function test_legacy_show_redirects_to_crm_open(): void
    {
        $user = $this->designer();
        $client = Client::query()->create([
            'user_id' => $user->id,
            'full_name' => 'Redirect Me',
            'client_type' => 'person',
            'phone' => '+77005550000',
            'email' => 'redir@example.com',
            'status' => 'new',
        ]);

        $this->actingAs($user)
            ->get('/clients/'.$client->id)
            ->assertRedirect(route('clients.index', ['open' => $client->id]));
    }

    public function test_delete_with_projects_requires_confirm_then_soft_deletes(): void
    {
        $user = $this->designer();
        app(PipelineService::class)->ensureDefaultsForUser((int) $user->id);

        $client = Client::query()->create([
            'user_id' => $user->id,
            'full_name' => 'Has Projects',
            'client_type' => 'person',
            'phone' => '+77006660000',
            'email' => 'has@example.com',
            'status' => 'new',
        ]);

        Project::query()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'name' => 'Keep me',
            'status' => ProjectStatus::InWork->value,
            'start_date' => now()->toDateString(),
            'planned_end_date' => now()->addMonth()->toDateString(),
            'planned_cost' => 1000,
            'actual_cost' => 0,
            'moderation_status' => 'approved',
        ]);

        $this->actingAs($user)
            ->deleteJson('/clients/delete/'.$client->id)
            ->assertStatus(422)
            ->assertJsonPath('needs_confirm', true);

        $this->actingAs($user)
            ->deleteJson('/clients/delete/'.$client->id, ['confirm' => true])
            ->assertOk();

        $this->assertSoftDeleted('clients', ['id' => $client->id]);
        $this->assertDatabaseHas('projects', ['name' => 'Keep me']);
    }

    public function test_cannot_open_foreign_client(): void
    {
        $owner = $this->designer();
        $intruder = $this->designer();
        $client = Client::query()->create([
            'user_id' => $owner->id,
            'full_name' => 'Secret',
            'client_type' => 'person',
            'phone' => '+77007770000',
            'email' => 'secret@example.com',
            'status' => 'new',
        ]);

        $this->actingAs($intruder)->getJson('/clients/'.$client->id)->assertNotFound();
    }
}
