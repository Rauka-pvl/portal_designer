<?php

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectStages;
use App\Models\ProjectStageStep;
use App\Models\Supplier;
use App\Models\Supplier_orders;
use App\Models\Template;
use App\Models\User;
use App\Services\Crm\PipelineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectChecklistsCrmTest extends TestCase
{
    use RefreshDatabase;

    private function designer(): User
    {
        return User::factory()->create([
            'role' => 'designer',
            'subscription_trial_ends_at' => now()->addDays(14),
        ]);
    }

    private function seedProject(User $user): Project
    {
        $client = Client::query()->create([
            'user_id' => $user->id,
            'full_name' => 'Клиент Чек-листов',
            'client_type' => 'person',
            'phone' => '+77002220000',
            'email' => 'checklist-client@example.com',
            'status' => 'new',
        ]);

        return Project::query()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'name' => 'Проект чек-листов',
            'status' => ProjectStatus::ContractNegotiation->value,
            'start_date' => now()->toDateString(),
            'planned_end_date' => now()->addMonth()->toDateString(),
            'planned_cost' => 0,
            'actual_cost' => 0,
            'moderation_status' => 'approved',
        ]);
    }

    public function test_crm_page_includes_checklist_modals_and_hides_footer_markup(): void
    {
        $user = $this->designer();
        app(PipelineService::class)->ensureDefaultsForUser((int) $user->id);

        $html = $this->actingAs($user)->get('/projects')->assertOk()->getContent();

        $this->assertStringContainsString('id="checklist-modal-root"', $html);
        $this->assertStringContainsString('id="checklist-detail-root"', $html);
        $this->assertStringContainsString('id="ov-checklists-list"', $html);
        $this->assertStringContainsString('id="ov-add-checklist"', $html);
        $this->assertStringContainsString('CrmChecklists', $html);
        $this->assertStringContainsString('ov-project-footer', $html);
        $this->assertStringContainsString("name !== 'general'", $html);
        $this->assertStringContainsString(__('projects.checklists_empty_title'), $html);
        $this->assertStringContainsString(__('projects.create_checklist'), $html);
        $this->assertStringContainsString('checklist-steps', $html);
    }

    public function test_can_create_checklist_stage_via_project_update(): void
    {
        $user = $this->designer();
        app(PipelineService::class)->ensureDefaultsForUser((int) $user->id);
        $project = $this->seedProject($user);

        $system = Template::query()->create([
            'user_id' => null,
            'name' => 'Обмер base',
            'type' => 'measurement',
            'steps' => ['Пункт A', 'Пункт B'],
        ]);

        $this->actingAs($user)->putJson('/projects/'.$project->id, [
            'name' => $project->name,
            'status' => $project->status,
            'stages' => [
                [
                    'stage_type' => 'measurement',
                    'template_id' => $system->id,
                    'deadline' => now()->addDays(5)->toDateString(),
                    'responsible_id' => $user->id,
                    'assign_task' => 0,
                    'steps' => [
                        ['title' => 'Пункт A', 'result_status' => 'pending'],
                        ['title' => 'Пункт B изменён', 'result_status' => 'pending'],
                        ['title' => 'Свой пункт', 'result_status' => 'pending'],
                    ],
                ],
            ],
        ])->assertOk()->assertJsonPath('success', true);

        $system->refresh();
        $this->assertSame(['Пункт A', 'Пункт B'], $system->steps);

        $stage = ProjectStages::query()->where('project_id', $project->id)->first();
        $this->assertNotNull($stage);
        $this->assertSame('measurement', $stage->stage_type);
        $this->assertSame($user->id, (int) $stage->responsible_id);
        $this->assertCount(3, $stage->steps);
        $this->assertSame('Пункт B изменён', $stage->steps()->orderBy('order')->skip(1)->first()->title);
    }

    public function test_step_ids_preserved_on_checklist_update(): void
    {
        $user = $this->designer();
        app(PipelineService::class)->ensureDefaultsForUser((int) $user->id);
        $project = $this->seedProject($user);

        $stage = ProjectStages::query()->create([
            'project_id' => $project->id,
            'stage_type' => 'planning',
            'deadline' => now()->addWeek()->toDateString(),
            'responsible_id' => $user->id,
            'assign_task' => false,
            'order' => 0,
        ]);
        $step1 = ProjectStageStep::query()->create([
            'project_stage_id' => $stage->id,
            'title' => 'Шаг 1',
            'result_status' => 'pending',
            'result_comment' => 'Результат 1',
            'order' => 0,
        ]);
        $step2 = ProjectStageStep::query()->create([
            'project_stage_id' => $stage->id,
            'title' => 'Шаг 2',
            'result_status' => 'done',
            'order' => 1,
        ]);

        $this->actingAs($user)->putJson('/projects/'.$project->id, [
            'name' => $project->name,
            'status' => $project->status,
            'stages' => [
                [
                    'id' => $stage->id,
                    'stage_type' => 'planning',
                    'deadline' => $stage->deadline,
                    'responsible_id' => $user->id,
                    'steps' => [
                        ['id' => $step1->id, 'title' => 'Шаг 1', 'result_status' => 'pending', 'result_comment' => 'Результат 1'],
                        ['id' => $step2->id, 'title' => 'Шаг 2 обновлён', 'result_status' => 'done'],
                    ],
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('project_stages_steps', [
            'id' => $step1->id,
            'title' => 'Шаг 1',
            'result_comment' => 'Результат 1',
        ]);
        $this->assertDatabaseHas('project_stages_steps', [
            'id' => $step2->id,
            'title' => 'Шаг 2 обновлён',
            'result_status' => 'done',
        ]);
    }

    public function test_can_save_step_result_and_use_in_supply(): void
    {
        $user = $this->designer();
        app(PipelineService::class)->ensureDefaultsForUser((int) $user->id);
        $project = $this->seedProject($user);
        $supplier = Supplier::query()->create([
            'user_id' => $user->id,
            'created_by_user_id' => $user->id,
            'name' => 'Поставщик ЧЛ',
            'profile_status' => 'active',
            'moderation_status' => 'approved',
        ]);

        $stage = ProjectStages::query()->create([
            'project_id' => $project->id,
            'stage_type' => 'equipment',
            'order' => 0,
        ]);
        $stepA = ProjectStageStep::query()->create([
            'project_stage_id' => $stage->id,
            'title' => 'Диван',
            'result_status' => 'pending',
            'order' => 0,
        ]);
        $stepB = ProjectStageStep::query()->create([
            'project_stage_id' => $stage->id,
            'title' => 'Стол',
            'result_status' => 'pending',
            'result_comment' => 'Старый',
            'order' => 1,
        ]);

        $this->actingAs($user)->putJson('/checklist-steps/'.$stepA->id, [
            'result_status' => 'done',
            'result_comment' => 'Диван серый 2 шт',
        ])->assertOk()->assertJsonPath('success', true);

        $stepA->refresh();
        $stepB->refresh();
        $this->assertSame('done', $stepA->result_status);
        $this->assertSame('Диван серый 2 шт', $stepA->result_comment);
        $this->assertSame('Старый', $stepB->result_comment);

        $this->actingAs($user)->postJson('/supplier-orders', [
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'summa' => 10000,
            'date_planned' => now()->addDays(3)->toDateString(),
            'send_to_supplier' => 0,
            'action' => 'save',
            'included_step_ids' => [$stepA->id],
            'product_items' => json_encode([]),
        ])->assertOk()->assertJsonPath('success', true);

        $order = Supplier_orders::query()->where('project_id', $project->id)->first();
        $this->assertNotNull($order);
        $included = is_array($order->included_step_ids) ? $order->included_step_ids : [];
        $this->assertContains($stepA->id, array_map('intval', $included));
        $this->assertNotContains($stepB->id, array_map('intval', $included));

        $json = $this->actingAs($user)->getJson('/supplier-orders/'.$order->id)->assertOk()->json();
        $steps = $json['included_steps'] ?? [];
        $this->assertCount(1, $steps);
        $this->assertSame('Диван серый 2 шт', $steps[0]['result_comment'] ?? null);
        $this->assertSame('Диван', $steps[0]['title'] ?? null);
    }

    public function test_supply_form_markup_shows_checklist_result_ui(): void
    {
        $user = $this->designer();
        app(PipelineService::class)->ensureDefaultsForUser((int) $user->id);

        $html = $this->actingAs($user)->get('/projects')->assertOk()->getContent();

        $this->assertStringContainsString(__('projects.supply_checklist_materials'), $html);
        $this->assertStringContainsString(__('projects.supply_checklist_hint'), $html);
        $this->assertStringContainsString('crm-section-title-plain', $html);
        $this->assertStringContainsString('function renderProjectSteps', $html);
        $this->assertStringContainsString('crm-supply-step-card', $html);
        $this->assertStringContainsString('result_comment', $html);
        $this->assertStringContainsString('stepsSelectedCount', $html);
        $this->assertStringContainsString('stepsSelectAll', $html);
        $this->assertStringContainsString(__('projects.supply_checklist_result_label'), $html);
    }

    public function test_cannot_attach_checklist_steps_from_foreign_project(): void
    {
        $owner = $this->designer();
        $other = $this->designer();
        app(PipelineService::class)->ensureDefaultsForUser((int) $owner->id);
        app(PipelineService::class)->ensureDefaultsForUser((int) $other->id);

        $project = $this->seedProject($owner);
        $foreignProject = $this->seedProject($other);
        $supplier = Supplier::query()->create([
            'user_id' => $owner->id,
            'created_by_user_id' => $owner->id,
            'name' => 'Поставщик',
            'profile_status' => 'active',
            'moderation_status' => 'approved',
        ]);

        $foreignStage = ProjectStages::query()->create([
            'project_id' => $foreignProject->id,
            'stage_type' => 'measurement',
            'order' => 0,
        ]);
        $foreignStep = ProjectStageStep::query()->create([
            'project_stage_id' => $foreignStage->id,
            'title' => 'Чужой',
            'result_status' => 'done',
            'result_comment' => 'Секрет',
            'order' => 0,
        ]);

        $this->actingAs($owner)->postJson('/supplier-orders', [
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'summa' => 1000,
            'date_planned' => now()->toDateString(),
            'send_to_supplier' => 0,
            'action' => 'save',
            'included_step_ids' => [$foreignStep->id],
            'product_items' => json_encode([]),
        ])->assertStatus(422);
    }

    public function test_user_template_crud_and_system_protected(): void
    {
        $user = $this->designer();
        app(PipelineService::class)->ensureDefaultsForUser((int) $user->id);

        $system = Template::query()->create([
            'user_id' => null,
            'name' => 'System',
            'type' => 'drawings',
            'steps' => ['A'],
        ]);

        $create = $this->actingAs($user)->postJson('/projects/templates', [
            'name' => 'Мой шаблон',
            'type' => 'drawings',
            'steps' => ['Один', 'Два'],
        ])->assertOk()->json();

        $tplId = (int) ($create['template']['id'] ?? 0);
        $this->assertGreaterThan(0, $tplId);

        $list = $this->actingAs($user)->getJson('/projects/templates')->assertOk()->json('templates');
        $types = collect($list)->pluck('type')->unique()->values();
        $this->assertTrue($types->contains('drawings'));
        $owned = collect($list)->firstWhere('id', $tplId);
        $this->assertTrue((bool) ($owned['is_owned'] ?? false));

        $this->actingAs($user)->deleteJson('/projects/templates/'.$system->id)->assertForbidden();
        $this->actingAs($user)->deleteJson('/projects/templates/'.$tplId)->assertOk();
        $this->assertSoftDeleted('templates', ['id' => $tplId]);
    }

    public function test_project_payload_includes_checklist_progress_fields(): void
    {
        $user = $this->designer();
        app(PipelineService::class)->ensureDefaultsForUser((int) $user->id);
        $project = $this->seedProject($user);

        $stage = ProjectStages::query()->create([
            'project_id' => $project->id,
            'stage_type' => 'visualization',
            'deadline' => now()->subDay()->toDateString(),
            'responsible_id' => $user->id,
            'order' => 0,
        ]);
        ProjectStageStep::query()->create([
            'project_stage_id' => $stage->id,
            'title' => 'Рендер',
            'result_status' => 'pending',
            'order' => 0,
        ]);
        ProjectStageStep::query()->create([
            'project_stage_id' => $stage->id,
            'title' => 'Правки',
            'result_status' => 'done',
            'order' => 1,
        ]);

        $payload = $this->actingAs($user)->getJson('/projects/'.$project->id)->assertOk()->json();
        $row = $payload['stages'][0] ?? null;
        $this->assertNotNull($row);
        $this->assertSame(2, (int) $row['steps_total']);
        $this->assertSame(1, (int) $row['steps_done']);
        $this->assertSame(50, (int) $row['progress_percent']);
        $this->assertTrue((bool) $row['is_overdue']);
        $this->assertSame($user->name, $row['responsible_name']);
    }

    public function test_deleting_template_does_not_delete_checklist_or_results(): void
    {
        $user = $this->designer();
        app(PipelineService::class)->ensureDefaultsForUser((int) $user->id);
        $project = $this->seedProject($user);

        $tpl = Template::query()->create([
            'user_id' => $user->id,
            'name' => 'Удаляемый',
            'type' => 'estimate',
            'steps' => ['Смета 1'],
        ]);
        $stage = ProjectStages::query()->create([
            'project_id' => $project->id,
            'stage_type' => 'estimate',
            'template_id' => $tpl->id,
            'order' => 0,
        ]);
        $step = ProjectStageStep::query()->create([
            'project_stage_id' => $stage->id,
            'title' => 'Смета 1',
            'result_status' => 'done',
            'result_comment' => 'Итог',
            'order' => 0,
        ]);

        $this->actingAs($user)->deleteJson('/projects/templates/'.$tpl->id)->assertOk();

        $this->assertDatabaseHas('project_stages', ['id' => $stage->id]);
        $this->assertDatabaseHas('project_stages_steps', [
            'id' => $step->id,
            'result_comment' => 'Итог',
        ]);
    }

    public function test_checklist_custom_name_is_stored_per_stage(): void
    {
        $user = $this->designer();
        app(PipelineService::class)->ensureDefaultsForUser((int) $user->id);
        $project = $this->seedProject($user);

        $this->actingAs($user)->putJson('/projects/'.$project->id, [
            'name' => $project->name,
            'status' => $project->status,
            'stages' => [
                [
                    'stage_type' => 'measurement',
                    'name' => 'Обмер кухни',
                    'deadline' => now()->addDays(2)->toDateString(),
                    'responsible_id' => $user->id,
                    'steps' => [
                        ['title' => 'Углы', 'result_status' => 'pending'],
                    ],
                ],
                [
                    'stage_type' => 'planning',
                    'name' => 'Планировка зала',
                    'deadline' => now()->addDays(10)->toDateString(),
                    'responsible_id' => $user->id,
                    'steps' => [
                        ['title' => 'Зоны', 'result_status' => 'pending'],
                    ],
                ],
            ],
        ])->assertOk();

        $payload = $this->actingAs($user)->getJson('/projects/'.$project->id)->assertOk()->json();
        $names = collect($payload['stages'])->pluck('name')->all();
        $this->assertContains('Обмер кухни', $names);
        $this->assertContains('Планировка зала', $names);
        $this->assertCount(2, collect($payload['stages'])->pluck('deadline')->filter()->unique());
    }
}
