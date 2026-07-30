<?php

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\PassportObject;
use App\Models\PipelineStage;
use App\Models\Project;
use App\Models\ProjectObjectDetail;
use App\Models\User;
use App\Services\Crm\PipelineService;
use App\Support\AccountPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmRedesignTest extends TestCase
{
    use RefreshDatabase;

    private function designer(): User
    {
        return User::factory()->create([
            'role' => 'designer',
            'subscription_trial_ends_at' => now()->addDays(14),
        ]);
    }

    private function seedClient(User $user): Client
    {
        return Client::query()->create([
            'user_id' => $user->id,
            'full_name' => 'Тест Клиент',
            'client_type' => 'person',
            'phone' => '+77001112233',
            'email' => 'client@example.com',
            'status' => 'new',
        ]);
    }

    public function test_account_owner_permissions(): void
    {
        $designer = $this->designer();
        $supplier = User::factory()->create(['role' => 'supplier', 'must_change_password' => false]);

        $this->assertTrue(AccountPermissions::canManageProjectPipeline($designer));
        $this->assertFalse(AccountPermissions::canManageProjectPipeline($supplier));
    }

    public function test_pipelines_seeded_and_verify_command(): void
    {
        $user = $this->designer();
        app(PipelineService::class)->ensureDefaultsForUser((int) $user->id);

        $this->artisan('crm:verify-migration')->assertSuccessful();
    }

    public function test_can_create_project_without_passport_object(): void
    {
        $user = $this->designer();
        app(PipelineService::class)->ensureDefaultsForUser((int) $user->id);
        $client = $this->seedClient($user);

        $response = $this->actingAs($user)->postJson('/projects', [
            'name' => 'CRM Проект без объекта',
            'status' => ProjectStatus::ContractNegotiation->value,
            'client_id' => $client->id,
            'city' => 'Алматы',
            'object_type' => 'apartment',
            'object_address' => 'ул. Абая 10',
            'apartment_floor' => '5',
            'apartment_entrance' => '2',
            'apartment' => '45',
            'area' => 80,
            'repair_budget_planned' => 8000000,
            'repair_budget_actual' => 7500000,
            'start_date' => now()->toDateString(),
            'planned_end_date' => now()->addMonth()->toDateString(),
            'links' => [
                ['title' => 'Drive', 'url' => 'https://example.com/drive'],
            ],
            'comment' => 'Комментарий к проекту',
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $projectId = (int) $response->json('project.id');

        $this->assertDatabaseHas('projects', [
            'id' => $projectId,
            'name' => 'CRM Проект без объекта',
            'client_id' => $client->id,
            'object_id' => null,
        ]);
        $this->assertDatabaseHas('project_object_details', [
            'project_id' => $projectId,
            'city' => 'Алматы',
            'address' => 'ул. Абая 10',
            'apartment' => '45',
            'type' => 'apartment',
        ]);

        $payload = $response->json('project');
        $this->assertEquals(100000.0, (float) $payload['repair_budget_per_m2_planned']);
        $this->assertEqualsWithDelta(93750.0, (float) $payload['repair_budget_per_m2_actual'], 0.01);
    }

    public function test_budget_per_m2_is_dash_when_area_empty(): void
    {
        $user = $this->designer();
        app(PipelineService::class)->ensureDefaultsForUser((int) $user->id);

        $response = $this->actingAs($user)->postJson('/projects', [
            'name' => 'Без площади',
            'status' => ProjectStatus::ContractNegotiation->value,
            'repair_budget_planned' => 1000000,
            'area' => null,
            'start_date' => now()->toDateString(),
            'planned_end_date' => now()->addMonth()->toDateString(),
        ]);

        $response->assertOk();
        $this->assertNull($response->json('project.repair_budget_per_m2_planned'));
    }

    public function test_can_move_project_status_and_log_activity(): void
    {
        $user = $this->designer();
        app(PipelineService::class)->ensureDefaultsForUser((int) $user->id);

        $project = Project::query()->create([
            'user_id' => $user->id,
            'name' => 'Move me',
            'status' => ProjectStatus::ContractNegotiation->value,
            'start_date' => now()->toDateString(),
            'planned_end_date' => now()->addMonth()->toDateString(),
            'planned_cost' => 0,
            'actual_cost' => 0,
            'moderation_status' => 'approved',
        ]);

        $to = ProjectStatus::ContractSigned->value;
        $this->actingAs($user)
            ->patchJson('/projects/'.$project->id.'/status', ['status' => $to])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame($to, $project->fresh()->status);
        $this->assertDatabaseHas('activity_events', [
            'subject_id' => $project->id,
            'event_type' => 'project.status_changed',
        ]);
    }

    public function test_owner_can_add_pipeline_column(): void
    {
        $user = $this->designer();
        app(PipelineService::class)->ensureDefaultsForUser((int) $user->id);

        $this->actingAs($user)->postJson('/pipelines/stages', [
            'type' => 'project',
            'name' => 'Архив',
            'color' => '#64748b',
        ])->assertOk()->assertJsonPath('success', true);
    }

    public function test_non_owner_cannot_manage_another_users_pipeline_stage(): void
    {
        $owner = $this->designer();
        $intruder = $this->designer();
        app(PipelineService::class)->ensureDefaultsForUser((int) $owner->id);
        app(PipelineService::class)->ensureDefaultsForUser((int) $intruder->id);

        $stage = PipelineStage::query()
            ->whereHas('pipeline', fn ($q) => $q->where('user_id', $owner->id)->where('type', 'project'))
            ->firstOrFail();

        $this->actingAs($intruder)->putJson('/pipelines/stages/'.$stage->id, [
            'name' => 'Hacked',
        ])->assertNotFound();
    }

    public function test_delete_empty_pipeline_column_with_confirm(): void
    {
        $user = $this->designer();
        app(PipelineService::class)->ensureDefaultsForUser((int) $user->id);

        $stage = PipelineStage::query()
            ->whereHas('pipeline', fn ($q) => $q->where('user_id', $user->id)->where('type', 'project'))
            ->where('system_key', ProjectStatus::InWork->value)
            ->firstOrFail();

        $this->actingAs($user)
            ->deleteJson('/pipelines/stages/'.$stage->id, ['confirm' => true])
            ->assertOk();
    }

    public function test_delete_column_with_projects_requires_target_then_moves(): void
    {
        $user = $this->designer();
        app(PipelineService::class)->ensureDefaultsForUser((int) $user->id);

        $from = ProjectStatus::ContractNegotiation->value;
        $to = ProjectStatus::InWork->value;

        $project = Project::query()->create([
            'user_id' => $user->id,
            'name' => 'In column',
            'status' => $from,
            'start_date' => now()->toDateString(),
            'planned_end_date' => now()->addMonth()->toDateString(),
            'planned_cost' => 0,
            'actual_cost' => 0,
            'moderation_status' => 'approved',
        ]);

        $fromStage = PipelineStage::query()
            ->whereHas('pipeline', fn ($q) => $q->where('user_id', $user->id)->where('type', 'project'))
            ->where('system_key', $from)
            ->firstOrFail();
        $toStage = PipelineStage::query()
            ->whereHas('pipeline', fn ($q) => $q->where('user_id', $user->id)->where('type', 'project'))
            ->where('system_key', $to)
            ->firstOrFail();

        $this->actingAs($user)
            ->deleteJson('/pipelines/stages/'.$fromStage->id, ['confirm' => true])
            ->assertStatus(422);

        $this->actingAs($user)
            ->deleteJson('/pipelines/stages/'.$fromStage->id, [
                'confirm' => true,
                'target_stage_id' => $toStage->id,
            ])
            ->assertOk();

        $this->assertSame($to, $project->fresh()->status);
    }

    public function test_can_comment_on_project_activity_feed(): void
    {
        $user = $this->designer();
        $project = Project::query()->create([
            'user_id' => $user->id,
            'name' => 'Feed',
            'status' => ProjectStatus::ContractNegotiation->value,
            'start_date' => now()->toDateString(),
            'planned_end_date' => now()->addMonth()->toDateString(),
            'planned_cost' => 0,
            'actual_cost' => 0,
            'moderation_status' => 'approved',
        ]);

        $this->actingAs($user)
            ->postJson('/projects/'.$project->id.'/comments', ['body' => 'Привет'])
            ->assertOk();

        $this->actingAs($user)
            ->getJson('/projects/'.$project->id.'/activity?filter=comments')
            ->assertOk()
            ->assertJsonPath('data.0.body', 'Привет');
    }

    public function test_legacy_object_data_migrates_into_project_details(): void
    {
        $user = $this->designer();
        $client = $this->seedClient($user);
        $object = PassportObject::query()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'city' => 'Астана',
            'address' => 'ул. Legacy 1',
            'type' => 'house',
            'status' => 'new',
            'area' => 120,
            'apartment_floor' => '1',
            'moderation_status' => 'approved',
        ]);

        $project = Project::query()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'object_id' => $object->id,
            'name' => 'Legacy',
            'status' => ProjectStatus::ContractNegotiation->value,
            'start_date' => now()->toDateString(),
            'planned_end_date' => now()->addMonth()->toDateString(),
            'planned_cost' => 0,
            'actual_cost' => 0,
            'moderation_status' => 'approved',
        ]);

        ProjectObjectDetail::syncFromPassport($project, $object);
        $detail = ProjectObjectDetail::query()->where('project_id', $project->id)->first();
        $this->assertNotNull($detail);
        $this->assertSame('ул. Legacy 1', $detail->address);
        $this->assertSame('Астана', $detail->city);

        $snapshot = $project->fresh(['object', 'objectDetails', 'client'])->propertySnapshot();
        $this->assertSame('ул. Legacy 1', $snapshot['address']);
        $this->assertSame($client->id, (int) $snapshot['client_id']);
    }

    public function test_supplier_orders_index_redirects_to_projects(): void
    {
        $user = $this->designer();
        $this->actingAs($user)->get('/supplier-orders')->assertRedirect(route('projects.index'));
    }

    public function test_objects_index_redirects_to_projects(): void
    {
        $user = $this->designer();
        $this->actingAs($user)->get('/objects')->assertRedirect(route('projects.index'));
    }

    public function test_projects_crm_page_loads(): void
    {
        $user = $this->designer();
        app(PipelineService::class)->ensureDefaultsForUser((int) $user->id);
        $this->actingAs($user)->get('/projects')->assertOk();
    }

    public function test_projects_crm_renders_single_view_mode_markup(): void
    {
        $user = $this->designer();
        app(PipelineService::class)->ensureDefaultsForUser((int) $user->id);

        $html = $this->actingAs($user)->get('/projects')->assertOk()->getContent();

        $this->assertStringContainsString('id="crm-kanban"', $html);
        $this->assertStringContainsString('id="crm-list"', $html);
        $this->assertStringContainsString('class="crm-board"', $html);
        $this->assertStringContainsString('crm-list-panel is-hidden', $html);
        $this->assertStringContainsString('crm-main-fill', $html);
        $this->assertStringNotContainsString('<h1 class="text-lg font-semibold', $html);
        $this->assertStringNotContainsString('crm-pipeline-title', $html);
        $this->assertStringContainsString('id="crm-create-btn"', $html);
        $this->assertStringContainsString('data-view="kanban"', $html);
        $this->assertStringContainsString('data-view="list"', $html);
        $this->assertStringContainsString('aria-pressed', $html);
        $this->assertStringContainsString('crm.projects.view', $html);
        $this->assertStringContainsString('setView(', $html);
        $this->assertStringContainsString('is-hidden', $html);
        $this->assertStringContainsString(__('projects.no_projects_yet'), $html);
        $this->assertStringContainsString(__('projects.meta_client'), $html);
        $this->assertStringContainsString('i18n.currency', $html);
        $this->assertStringContainsString('formatDate', $html);
        $this->assertStringContainsString('debounce', $html);
    }

    public function test_project_payload_includes_owner_and_checklist_progress(): void
    {
        $user = $this->designer();
        app(PipelineService::class)->ensureDefaultsForUser((int) $user->id);
        $client = $this->seedClient($user);

        $project = Project::query()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'name' => 'Payload Project',
            'status' => ProjectStatus::ContractNegotiation->value,
            'start_date' => now()->toDateString(),
            'planned_end_date' => now()->addDays(10)->toDateString(),
            'planned_cost' => 4444,
            'actual_cost' => 0,
            'moderation_status' => 'approved',
        ]);

        $payload = $this->actingAs($user)
            ->getJson('/projects/'.$project->id)
            ->assertOk()
            ->json();

        $this->assertSame($user->name, $payload['owner_name']);
        $this->assertSame($client->full_name, $payload['client_name']);
        $this->assertArrayHasKey('checklist_progress', $payload);
        $this->assertArrayHasKey('has_delayed_supply', $payload);
        $this->assertArrayHasKey('repair_budget_planned', $payload);
    }

    public function test_theme_toggle_label_is_not_dashboard(): void
    {
        $user = $this->designer();
        app(PipelineService::class)->ensureDefaultsForUser((int) $user->id);

        $html = $this->actingAs($user)->get('/projects')->assertOk()->getContent();

        $this->assertStringContainsString(__('dashboard.dark_theme'), $html);
        $this->assertStringContainsString(__('dashboard.light_theme'), $html);
        $this->assertStringNotContainsString('id="theme-text">'.__('dashboard.dashboard'), $html);
    }
}
