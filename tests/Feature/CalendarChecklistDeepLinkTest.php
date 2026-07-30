<?php

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectStages;
use App\Models\ProjectStageStep;
use App\Models\User;
use App\Services\Crm\PipelineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarChecklistDeepLinkTest extends TestCase
{
    use RefreshDatabase;

    private function designer(): User
    {
        return User::factory()->create([
            'role' => 'designer',
            'subscription_trial_ends_at' => now()->addDays(14),
        ]);
    }

    private function seedChecklist(User $user): array
    {
        $client = Client::query()->create([
            'user_id' => $user->id,
            'full_name' => 'Клиент календаря',
            'client_type' => 'person',
            'phone' => '+77003330000',
            'email' => 'calendar-checklist@example.com',
            'status' => 'new',
        ]);

        $project = Project::query()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'name' => 'Проект календаря',
            'status' => ProjectStatus::ContractNegotiation->value,
            'start_date' => now()->toDateString(),
            'planned_end_date' => now()->addMonth()->toDateString(),
            'planned_cost' => 0,
            'actual_cost' => 0,
            'moderation_status' => 'approved',
        ]);

        $deadline = now()->toDateString();

        $stage = ProjectStages::query()->create([
            'project_id' => $project->id,
            'stage_type' => 'measurement',
            'name' => 'Обмер',
            'deadline' => $deadline,
            'responsible_id' => $user->id,
            'assign_task' => false,
            'order' => 1,
        ]);

        $stepA = ProjectStageStep::query()->create([
            'project_stage_id' => $stage->id,
            'title' => 'Пункт А',
            'deadline' => $deadline,
            'responsible_id' => $user->id,
            'result_status' => 'pending',
            'result_comment' => 'Результат пункта А',
            'order' => 1,
        ]);

        $stepB = ProjectStageStep::query()->create([
            'project_stage_id' => $stage->id,
            'title' => 'Пункт Б',
            'deadline' => $deadline,
            'responsible_id' => $user->id,
            'result_status' => 'pending',
            'result_comment' => 'Результат пункта Б',
            'order' => 2,
        ]);

        return compact('project', 'stage', 'stepA', 'stepB');
    }

    public function test_calendar_events_link_to_tasks_checklist_deep_link(): void
    {
        $user = $this->designer();
        app(PipelineService::class)->ensureDefaultsForUser((int) $user->id);
        ['project' => $project, 'stage' => $stage, 'stepA' => $stepA] = $this->seedChecklist($user);

        $res = $this->actingAs($user)->getJson('/dashboard/events?start='.now()->startOfMonth()->toDateString().'&end='.now()->endOfMonth()->toDateString());
        $res->assertOk();

        $events = collect($res->json('events'))->where('source_type', 'checklist_step')->values();
        $this->assertNotEmpty($events);

        $event = $events->firstWhere('source_id', $stepA->id);
        $this->assertNotNull($event);
        $this->assertSame((int) $stage->id, (int) $event['project_stage_id']);
        $this->assertSame((int) $project->id, (int) $event['project_id']);
        $this->assertStringContainsString('/tasks', (string) $event['url_show']);
        $this->assertStringContainsString('checklist='.$stage->id, (string) $event['url_show']);
        $this->assertStringContainsString('item='.$stepA->id, (string) $event['url_show']);
        $this->assertStringNotContainsString('/checklist-steps/', (string) $event['url_show']);
    }

    public function test_legacy_checklist_step_show_redirects_to_tasks_deep_link(): void
    {
        $user = $this->designer();
        ['project' => $project, 'stage' => $stage, 'stepA' => $stepA] = $this->seedChecklist($user);

        $this->actingAs($user)
            ->get('/checklist-steps/'.$stepA->id)
            ->assertRedirect(route('tasks.index', [
                'project' => $project->id,
                'checklist' => $stage->id,
                'item' => $stepA->id,
                'date' => $stepA->deadline,
            ]));
    }

    public function test_foreign_checklist_step_is_forbidden(): void
    {
        $owner = $this->designer();
        $stranger = $this->designer();
        ['stepA' => $stepA] = $this->seedChecklist($owner);

        $this->actingAs($stranger)
            ->get('/checklist-steps/'.$stepA->id)
            ->assertNotFound();
    }

    public function test_tasks_page_includes_reusable_checklist_card(): void
    {
        $user = $this->designer();

        $html = $this->actingAs($user)->get('/tasks')->assertOk()->getContent();

        $this->assertStringContainsString('id="checklist-detail-root"', $html);
        $this->assertStringContainsString('CrmChecklists', $html);
        $this->assertStringContainsString('openByIds', $html);
        $this->assertStringContainsString('data-checklist-event', $html);
    }

    public function test_crm_page_supports_checklist_deep_link_params(): void
    {
        $user = $this->designer();
        app(PipelineService::class)->ensureDefaultsForUser((int) $user->id);

        $html = $this->actingAs($user)->get('/projects')->assertOk()->getContent();

        $this->assertStringContainsString("params.get('checklist')", $html);
        $this->assertStringContainsString('openDetail(Number(checklistId)', $html);
    }

    public function test_step_result_remains_available_for_supply_selection(): void
    {
        $user = $this->designer();
        app(PipelineService::class)->ensureDefaultsForUser((int) $user->id);
        ['project' => $project, 'stepA' => $stepA, 'stepB' => $stepB] = $this->seedChecklist($user);

        $payload = $this->actingAs($user)->getJson('/projects/'.$project->id)->assertOk()->json();
        $steps = collect($payload['stages'][0]['steps'] ?? []);

        $this->assertSame('Результат пункта А', $steps->firstWhere('id', $stepA->id)['result_comment'] ?? null);
        $this->assertSame('Результат пункта Б', $steps->firstWhere('id', $stepB->id)['result_comment'] ?? null);
    }
}
