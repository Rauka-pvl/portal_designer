<?php

namespace Tests\Feature;

use App\Enums\DesignerTaskStatus;
use App\Models\Client;
use App\Models\DesignerTask;
use App\Models\Project;
use App\Models\User;
use App\Support\DesignerSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileApiRedesignTest extends TestCase
{
    use RefreshDatabase;

    private function designer(): User
    {
        return User::factory()->create([
            'role' => 'designer',
            'subscription_trial_ends_at' => now()->addDays(7),
        ]);
    }

    public function test_auth_login_format_unchanged(): void
    {
        $user = User::factory()->create([
            'role' => 'designer',
            'email' => 'mobile-login@example.com',
            'password' => bcrypt('Password1!'),
            'subscription_trial_ends_at' => now()->addDays(7),
        ]);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'Password1!',
            'portal' => 'designer',
        ])->assertOk()
            ->assertJsonStructure(['token', 'token_type', 'user'])
            ->assertJsonPath('token_type', 'Bearer');
    }

    public function test_dashboard_requires_auth_and_returns_metrics(): void
    {
        $this->getJson('/api/dashboard')->assertUnauthorized();

        Sanctum::actingAs($this->designer());
        $this->getJson('/api/dashboard?period=month')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'period' => ['type', 'date_from', 'date_to'],
                    'metrics' => [
                        'active_projects',
                        'overdue_projects',
                        'upcoming_deadlines',
                        'overdue_checklists',
                        'delayed_supplies',
                        'completed_projects',
                    ],
                    'charts',
                ],
            ]);
    }

    public function test_dashboard_custom_requires_dates(): void
    {
        Sanctum::actingAs($this->designer());
        $this->getJson('/api/dashboard?period=custom')
            ->assertStatus(422);
    }

    public function test_clients_list_paginated_and_project_without_passport(): void
    {
        $user = $this->designer();
        Sanctum::actingAs($user);

        $client = Client::query()->create([
            'user_id' => $user->id,
            'full_name' => 'Client A',
            'client_type' => 'person',
            'phone' => '+77000000001',
            'email' => 'a@example.com',
            'status' => 'new',
        ]);

        $this->getJson('/api/clients?per_page=10')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'per_page', 'total'], 'links']);

        $create = $this->postJson('/api/projects', [
            'name' => 'Project without passport',
            'client_id' => $client->id,
            'status' => 'contract_negotiation',
            'start_date' => now()->toDateString(),
        ]);

        $create->assertSuccessful()
            ->assertJsonPath('data.name', 'Project without passport')
            ->assertJsonMissingPath('data.object_id');
    }

    public function test_tasks_crud_kanban_and_nullable_project(): void
    {
        $user = $this->designer();
        Sanctum::actingAs($user);

        $create = $this->postJson('/api/tasks', [
            'title' => 'Call supplier',
            'description' => 'Ask for samples',
            'status' => DesignerTaskStatus::New->value,
            'due_at' => now()->addDay()->toIso8601String(),
            'assignee_id' => $user->id,
            'project_id' => null,
        ]);

        $create->assertSuccessful()->assertJsonPath('data.source_type', 'designer_task');
        $taskId = (int) $create->json('data.id');

        $this->patchJson('/api/tasks/'.$taskId.'/status', [
            'status' => DesignerTaskStatus::InProgress->value,
        ])->assertSuccessful()->assertJsonPath('data.status', 'in_progress');

        $this->getJson('/api/tasks/kanban')
            ->assertOk()
            ->assertJsonStructure(['data' => ['new', 'in_progress', 'completed', 'cancelled']]);

        $this->getJson('/api/tasks/calendar?date_from='.now()->toDateString().'&date_to='.now()->addDays(7)->toDateString())
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_subscription_plans_include_corporate_price(): void
    {
        Sanctum::actingAs($this->designer());
        $response = $this->getJson('/api/subscription/plans')->assertOk();
        $plans = collect($response->json('data.plans'));
        $corporate = $plans->firstWhere('key', DesignerSubscription::PLAN_CORPORATE);
        $this->assertNotNull($corporate);
        $this->assertSame('29990.00', $corporate['price']);
        $this->assertSame(5, (int) $corporate['included_seats']);
        $this->assertSame('KZT', $corporate['currency']);
    }

    public function test_team_assignees_personal_returns_self(): void
    {
        $user = $this->designer();
        Sanctum::actingAs($user);

        $this->getJson('/api/team/assignees')
            ->assertOk()
            ->assertJsonFragment(['id' => $user->id, 'name' => $user->name]);
    }

    public function test_foreign_client_is_not_accessible(): void
    {
        $owner = $this->designer();
        $other = $this->designer();
        $client = Client::query()->create([
            'user_id' => $owner->id,
            'full_name' => 'Secret',
            'client_type' => 'person',
            'phone' => '+77000000002',
            'email' => 'secret@example.com',
            'status' => 'new',
        ]);

        Sanctum::actingAs($other);
        $this->getJson('/api/clients/'.$client->id)->assertNotFound();
    }

    public function test_client_stages_list_includes_custom_and_can_create(): void
    {
        $user = $this->designer();
        Sanctum::actingAs($user);

        $list = $this->getJson('/api/client-stages')->assertOk();
        $keys = collect($list->json('data'))->pluck('system_key');
        $this->assertTrue($keys->contains('new'));

        $created = $this->postJson('/api/client-stages', [
            'name' => 'Ждёт звонка',
            'color' => '#22c55e',
        ])->assertCreated();

        $this->assertSame('Ждёт звонка', $created->json('data.name'));
        $this->assertFalse((bool) $created->json('data.is_system'));
        $this->assertStringStartsWith('custom_', (string) $created->json('data.system_key'));

        $this->getJson('/api/client-stages')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Ждёт звонка']);
    }

    public function test_task_sort_whitelist_rejects_unknown_column_via_safe_default(): void
    {
        $user = $this->designer();
        Sanctum::actingAs($user);
        DesignerTask::query()->create([
            'creator_id' => $user->id,
            'assignee_id' => $user->id,
            'title' => 'A',
            'status' => DesignerTaskStatus::New,
            'due_at' => now()->addDay(),
        ]);

        $this->getJson('/api/tasks?sort=password&direction=asc')->assertOk();
    }
}
