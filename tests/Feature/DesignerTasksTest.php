<?php

namespace Tests\Feature;

use App\Enums\DesignerTaskStatus;
use App\Enums\TeamRole;
use App\Models\DesignerTask;
use App\Models\Project;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Team\TeamService;
use App\Support\DesignerSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesignerTasksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['subscription.allow_stub_payments' => true]);
    }

    private function designer(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'designer',
            'subscription_trial_ends_at' => now()->addDays(14),
            'subscription_plan' => DesignerSubscription::PLAN_PRO,
        ], $attrs));
    }

    private function corporateOwner(): array
    {
        $owner = $this->designer([
            'subscription_plan' => DesignerSubscription::PLAN_CORPORATE,
            'subscription_ends_at' => now()->addMonth(),
            'subscription_trial_ends_at' => null,
            'subscription_trial_used' => true,
        ]);
        $team = app(TeamService::class)->activateCorporateForOwner($owner);

        return [$owner, $team];
    }

    public function test_tasks_page_has_kanban_and_calendar_switch(): void
    {
        $user = $this->designer();
        $html = $this->actingAs($user)->get(route('tasks.index'))->assertOk()->getContent();

        $this->assertStringContainsString('data-view="kanban"', $html);
        $this->assertStringContainsString('data-view="calendar"', $html);
        $this->assertStringContainsString('tasks_view_mode', $html);
    }

    public function test_standard_can_create_task_without_project(): void
    {
        $user = $this->designer(['subscription_plan' => DesignerSubscription::PLAN_STANDARD]);

        $res = $this->actingAs($user)->postJson(route('tasks.store'), [
            'title' => 'Prepare moodboard',
            'description' => 'For client A',
            'due_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'assignee_id' => $user->id,
            'project_id' => null,
        ]);

        $res->assertCreated();
        $this->assertDatabaseHas('designer_tasks', [
            'title' => 'Prepare moodboard',
            'creator_id' => $user->id,
            'assignee_id' => $user->id,
            'project_id' => null,
            'status' => DesignerTaskStatus::New->value,
        ]);
    }

    public function test_personal_plan_cannot_assign_other_user(): void
    {
        $user = $this->designer();
        $other = $this->designer(['email' => 'other@ex.com']);

        $this->actingAs($user)->postJson(route('tasks.store'), [
            'title' => 'Hack assign',
            'due_at' => now()->addDay()->toDateTimeString(),
            'assignee_id' => $other->id,
        ])->assertStatus(422);
    }

    public function test_corporate_can_assign_team_member_and_notifies(): void
    {
        [$owner, $team] = $this->corporateOwner();
        $member = $this->designer([
            'email' => 'mate@ex.com',
            'subscription_ends_at' => null,
            'subscription_plan' => null,
            'subscription_trial_ends_at' => null,
        ]);
        app(TeamService::class)->addExistingUser($team, $owner, $member, TeamRole::Designer);

        $this->actingAs($owner)->postJson(route('tasks.store'), [
            'title' => 'Layout',
            'due_at' => now()->addDays(2)->toDateTimeString(),
            'assignee_id' => $member->id,
        ])->assertCreated();

        $task = DesignerTask::query()->where('title', 'Layout')->first();
        $this->assertNotNull($task);
        $this->assertSame((int) $owner->id, (int) $task->creator_id);
        $this->assertSame((int) $member->id, (int) $task->assignee_id);
        $this->assertSame((int) $team->id, (int) $task->team_id);
        $this->assertTrue(
            UserNotification::query()
                ->where('user_id', $member->id)
                ->where('action_key', 'task_assigned')
                ->exists()
        );
    }

    public function test_self_assign_does_not_notify(): void
    {
        $user = $this->designer();

        $this->actingAs($user)->postJson(route('tasks.store'), [
            'title' => 'Self task',
            'due_at' => now()->addDay()->toDateTimeString(),
            'assignee_id' => $user->id,
        ])->assertCreated();

        $this->assertSame(0, UserNotification::query()->where('user_id', $user->id)->where('action_key', 'task_assigned')->count());
    }

    public function test_designer_visibility_rules(): void
    {
        [$owner, $team] = $this->corporateOwner();
        $designer = $this->designer([
            'email' => 'd@ex.com',
            'subscription_ends_at' => null,
            'subscription_plan' => null,
            'subscription_trial_ends_at' => null,
        ]);
        $other = $this->designer([
            'email' => 'o@ex.com',
            'subscription_ends_at' => null,
            'subscription_plan' => null,
            'subscription_trial_ends_at' => null,
        ]);
        app(TeamService::class)->addExistingUser($team, $owner, $designer, TeamRole::Designer);
        app(TeamService::class)->addExistingUser($team, $owner, $other, TeamRole::Designer);

        $visible = DesignerTask::query()->create([
            'creator_id' => $owner->id,
            'assignee_id' => $designer->id,
            'team_id' => $team->id,
            'title' => 'Assigned to designer',
            'status' => DesignerTaskStatus::New,
            'due_at' => now()->addDay(),
        ]);
        $hidden = DesignerTask::query()->create([
            'creator_id' => $owner->id,
            'assignee_id' => $other->id,
            'team_id' => $team->id,
            'title' => 'Other task',
            'status' => DesignerTaskStatus::New,
            'due_at' => now()->addDay(),
        ]);

        $data = $this->actingAs($designer)->getJson(route('tasks.data'))->assertOk()->json('tasks');
        $ids = collect($data)->pluck('id')->all();
        $this->assertContains($visible->id, $ids);
        $this->assertNotContains($hidden->id, $ids);

        $ownerData = $this->actingAs($owner)->getJson(route('tasks.data'))->assertOk()->json('tasks');
        $ownerIds = collect($ownerData)->pluck('id')->all();
        $this->assertContains($visible->id, $ownerIds);
        $this->assertContains($hidden->id, $ownerIds);
    }

    public function test_status_drag_sets_completed_at(): void
    {
        $user = $this->designer();
        $task = DesignerTask::query()->create([
            'creator_id' => $user->id,
            'assignee_id' => $user->id,
            'title' => 'Finish me',
            'status' => DesignerTaskStatus::New,
            'due_at' => now()->addDay(),
        ]);

        $this->actingAs($user)->patchJson(route('tasks.status', $task), [
            'status' => DesignerTaskStatus::Completed->value,
        ])->assertOk();

        $task->refresh();
        $this->assertSame(DesignerTaskStatus::Completed, $task->status);
        $this->assertNotNull($task->completed_at);
    }

    public function test_cannot_link_foreign_project(): void
    {
        $user = $this->designer();
        $other = $this->designer(['email' => 'x@ex.com']);
        $project = Project::query()->create([
            'user_id' => $other->id,
            'name' => 'Foreign',
            'status' => 'lead',
            'start_date' => now()->toDateString(),
            'planned_end_date' => now()->addMonth()->toDateString(),
        ]);

        $this->actingAs($user)->postJson(route('tasks.store'), [
            'title' => 'Bad project',
            'due_at' => now()->addDay()->toDateTimeString(),
            'project_id' => $project->id,
        ])->assertStatus(422);
    }

    public function test_calendar_includes_designer_task_event(): void
    {
        $user = $this->designer();
        $due = now()->startOfMonth()->addDays(10)->setTime(15, 30);
        $task = DesignerTask::query()->create([
            'creator_id' => $user->id,
            'assignee_id' => $user->id,
            'title' => 'Cal task',
            'status' => DesignerTaskStatus::InProgress,
            'due_at' => $due,
        ]);

        $events = $this->actingAs($user)->getJson(route('tasks.events', [
            'start' => now()->startOfMonth()->toDateString(),
            'end' => now()->endOfMonth()->toDateString(),
        ]))->assertOk()->json('events');

        $match = collect($events)->firstWhere('id', 'designer_task:'.$task->id);
        $this->assertNotNull($match);
        $this->assertSame('designer_task', $match['source_type']);
        $this->assertSame('15:30', $match['time']);
    }

    public function test_reassign_notifies_new_only_once(): void
    {
        [$owner, $team] = $this->corporateOwner();
        $a = $this->designer(['email' => 'a2@ex.com', 'subscription_ends_at' => null, 'subscription_plan' => null, 'subscription_trial_ends_at' => null]);
        $b = $this->designer(['email' => 'b2@ex.com', 'subscription_ends_at' => null, 'subscription_plan' => null, 'subscription_trial_ends_at' => null]);
        app(TeamService::class)->addExistingUser($team, $owner, $a, TeamRole::Designer);
        app(TeamService::class)->addExistingUser($team, $owner, $b, TeamRole::Designer);

        $task = DesignerTask::query()->create([
            'creator_id' => $owner->id,
            'assignee_id' => $a->id,
            'team_id' => $team->id,
            'title' => 'Move',
            'status' => DesignerTaskStatus::New,
            'due_at' => now()->addDay(),
        ]);

        $this->actingAs($owner)->putJson(route('tasks.update', $task), [
            'title' => 'Move',
            'due_at' => now()->addDay()->toDateTimeString(),
            'assignee_id' => $b->id,
            'status' => DesignerTaskStatus::New->value,
        ])->assertOk();

        $this->assertSame(1, UserNotification::query()->where('user_id', $b->id)->where('action_key', 'task_assigned')->count());

        $this->actingAs($owner)->putJson(route('tasks.update', $task), [
            'title' => 'Move again',
            'due_at' => now()->addDay()->toDateTimeString(),
            'assignee_id' => $b->id,
            'status' => DesignerTaskStatus::New->value,
        ])->assertOk();

        $this->assertSame(1, UserNotification::query()->where('user_id', $b->id)->where('action_key', 'task_assigned')->count());
    }
}
