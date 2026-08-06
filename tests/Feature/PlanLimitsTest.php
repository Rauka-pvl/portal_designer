<?php

namespace Tests\Feature;

use App\Enums\TeamRole;
use App\Exceptions\PlanLimitExceeded;
use App\Models\DesignerTeam;
use App\Models\Project;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Billing\PlanCatalog;
use App\Services\Billing\PlanLimitService;
use App\Services\Team\TeamService;
use App\Support\DesignerSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PlanLimitsTest extends TestCase
{
    use RefreshDatabase;

    private function designer(array $attrs = []): User
    {
        $user = User::factory()->create(array_merge([
            'account_type' => 'designer',
        ], $attrs));

        $planKey = $attrs['subscription_plan'] ?? DesignerSubscription::PLAN_PRO;
        if ($planKey) {
            $plan = SubscriptionPlan::findByKey($planKey);
            if ($plan) {
                Subscription::create([
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'status' => 'active',
                    'starts_at' => now(),
                    'expires_at' => $attrs['subscription_ends_at'] ?? now()->addDays(20),
                ]);
            }
        }

        return $user;
    }

    private function makeProject(User $owner, ?int $teamId = null, array $attrs = []): Project
    {
        return Project::query()->create(array_merge([
            'user_id' => $owner->id,
            'team_id' => $teamId,
            'name' => 'Project',
            'status' => 'lead',
            'start_date' => now()->toDateString(),
            'planned_end_date' => now()->addMonth()->toDateString(),
        ], $attrs));
    }

    private function limits(): PlanLimitService
    {
        return app(PlanLimitService::class);
    }

    // --- Projects: individual plans ---

    public function test_base_allows_two_projects_and_blocks_third(): void
    {
        $user = $this->designer(['subscription_plan' => DesignerSubscription::PLAN_BASE]);

        $this->makeProject($user);
        $this->limits()->assertCanCreateProject($user); // 1 of 2 — still allowed

        $this->makeProject($user);
        $this->assertSame(2, $this->limits()->projectCountFor($user));

        try {
            $this->limits()->assertCanCreateProject($user->fresh());
            $this->fail('Third project must be blocked on Base');
        } catch (PlanLimitExceeded $e) {
            $this->assertSame('PROJECT_LIMIT_REACHED', $e->errorCode);
            $this->assertSame(2, $e->limit);
            $this->assertSame(2, $e->current);
        }
    }

    public function test_base_third_project_blocked_via_http_with_error_code(): void
    {
        $user = $this->designer(['subscription_plan' => DesignerSubscription::PLAN_BASE]);
        $this->makeProject($user);
        $this->makeProject($user);

        $response = $this->actingAs($user)->postJson(route('projects.store'), [
            'name' => 'Third',
            'status' => 'lead',
        ]);

        $response->assertStatus(422);
        $this->assertSame('PROJECT_LIMIT_REACHED', $response->json('error'));
        $this->assertSame(2, Project::query()->where('user_id', $user->id)->count());
    }

    public function test_standard_allows_five_projects_and_blocks_sixth(): void
    {
        $user = $this->designer(['subscription_plan' => DesignerSubscription::PLAN_STANDARD]);

        for ($i = 0; $i < 5; $i++) {
            $this->makeProject($user);
        }

        $this->expectException(PlanLimitExceeded::class);
        $this->limits()->assertCanCreateProject($user->fresh());
    }

    public function test_pro_has_unlimited_projects(): void
    {
        $user = $this->designer(['subscription_plan' => DesignerSubscription::PLAN_PRO]);

        for ($i = 0; $i < 12; $i++) {
            $this->makeProject($user);
        }

        $this->assertNull($this->limits()->projectLimitFor($user));
        $this->limits()->assertCanCreateProject($user->fresh()); // no exception
        $this->assertTrue($this->limits()->canCreateProject($user));
    }

    public function test_soft_deleted_projects_do_not_count(): void
    {
        $user = $this->designer(['subscription_plan' => DesignerSubscription::PLAN_BASE]);

        $this->makeProject($user);
        $deleted = $this->makeProject($user);
        $deleted->delete(); // soft delete

        $this->assertSame(1, $this->limits()->projectCountFor($user));
        $this->assertTrue($this->limits()->canCreateProject($user));
    }

    public function test_completed_and_archived_projects_count_towards_limit(): void
    {
        $user = $this->designer(['subscription_plan' => DesignerSubscription::PLAN_BASE]);

        $this->makeProject($user, null, ['status' => 'completed', 'workflow_status' => 'completed']);
        $this->makeProject($user, null, ['status' => 'archive', 'workflow_status' => 'archived']);

        $this->assertSame(2, $this->limits()->projectCountFor($user));
        $this->assertFalse($this->limits()->canCreateProject($user));
    }

    // --- Projects: corporate plans count team-wide ---

    public function test_economy_counts_six_projects_across_whole_team(): void
    {
        $owner = $this->designer(['subscription_plan' => DesignerSubscription::PLAN_ECONOMY]);
        $member = $this->designer([
            'email' => 'mate@example.com',
            'subscription_plan' => null,
            'subscription_ends_at' => null,
        ]);

        $teams = app(TeamService::class);
        $team = $teams->activateCorporateForOwner($owner, null, SubscriptionPlan::findByKey('economy'));
        $invitation = $teams->addExistingUser($team, $owner, $member, TeamRole::Designer);
        $teams->acceptInvitation($member, $invitation);

        // 3 team projects + 3 personal projects of the member = 6 total
        for ($i = 0; $i < 3; $i++) {
            $this->makeProject($owner, $team->id);
            $this->makeProject($member);
        }

        $this->assertSame(6, $this->limits()->projectCountFor($owner));
        $this->assertSame(6, $this->limits()->projectCountFor($member));

        try {
            $this->limits()->assertCanCreateProject($member->fresh());
            $this->fail('Team project limit must block members too');
        } catch (PlanLimitExceeded $e) {
            $this->assertSame('PROJECT_LIMIT_REACHED', $e->errorCode);
            $this->assertSame(6, $e->limit);
        }
    }

    public function test_progress_allows_fifteen_team_projects(): void
    {
        $owner = $this->designer(['subscription_plan' => DesignerSubscription::PLAN_PROGRESS]);
        $team = app(TeamService::class)
            ->activateCorporateForOwner($owner, null, SubscriptionPlan::findByKey('progress'));

        for ($i = 0; $i < 15; $i++) {
            $this->makeProject($owner, $team->id);
        }

        $this->assertSame(15, $this->limits()->projectCountFor($owner));
        $this->assertFalse($this->limits()->canCreateProject($owner));
    }

    public function test_success_has_unlimited_projects(): void
    {
        $owner = $this->designer(['subscription_plan' => DesignerSubscription::PLAN_SUCCESS]);
        $team = app(TeamService::class)
            ->activateCorporateForOwner($owner, null, SubscriptionPlan::findByKey('success'));

        for ($i = 0; $i < 20; $i++) {
            $this->makeProject($owner, $team->id);
        }

        $this->assertTrue($this->limits()->canCreateProject($owner));
    }

    // --- Team seats ---

    public function test_economy_allows_three_users_including_owner(): void
    {
        $owner = $this->designer(['subscription_plan' => DesignerSubscription::PLAN_ECONOMY]);
        $team = app(TeamService::class)
            ->activateCorporateForOwner($owner, null, SubscriptionPlan::findByKey('economy'));

        $this->assertSame(3, $this->limits()->seatLimitFor($team->fresh()));
        $this->assertSame(1, $team->fresh()->activeMembersCount()); // owner seat
        $this->assertSame(2, $team->fresh()->seatsRemaining());
    }

    public function test_progress_allows_seven_users_including_owner(): void
    {
        $owner = $this->designer(['subscription_plan' => DesignerSubscription::PLAN_PROGRESS]);
        $team = app(TeamService::class)
            ->activateCorporateForOwner($owner, null, SubscriptionPlan::findByKey('progress'));

        $this->assertSame(7, $this->limits()->seatLimitFor($team->fresh()));
    }

    public function test_success_has_unlimited_seats(): void
    {
        $owner = $this->designer(['subscription_plan' => DesignerSubscription::PLAN_SUCCESS]);
        $team = app(TeamService::class)
            ->activateCorporateForOwner($owner, null, SubscriptionPlan::findByKey('success'));

        $this->assertNull($this->limits()->seatLimitFor($team->fresh()));
        $this->assertNull($team->fresh()->seatsRemaining());
        $this->assertTrue($team->fresh()->hasSeatAvailable());
    }

    public function test_cannot_exceed_limit_by_accepting_multiple_invitations(): void
    {
        $owner = $this->designer(['subscription_plan' => DesignerSubscription::PLAN_ECONOMY]);
        $teams = app(TeamService::class);
        $team = $teams->activateCorporateForOwner($owner, null, SubscriptionPlan::findByKey('economy'));

        $m1 = $this->designer(['email' => 's1@example.com', 'subscription_plan' => null, 'subscription_ends_at' => null]);
        $m2 = $this->designer(['email' => 's2@example.com', 'subscription_plan' => null, 'subscription_ends_at' => null]);

        $inv1 = $teams->addExistingUser($team, $owner, $m1, TeamRole::Designer);
        $teams->acceptInvitation($m1, $inv1); // team full now: owner + m1 = 2... economy = 3, so one more seat

        $inv2 = $teams->addExistingUser($team->fresh(), $owner, $m2, TeamRole::Designer);
        $teams->acceptInvitation($m2, $inv2); // owner + m1 + m2 = 3 = limit

        // Team is full: a further invitation must be rejected at send time,
        // and a pending one must be rejected at accept time.
        $this->assertSame(3, $team->fresh()->activeMembersCount());

        $m3 = $this->designer(['email' => 's3@example.com', 'subscription_plan' => null, 'subscription_ends_at' => null]);

        $this->expectException(ValidationException::class);
        $teams->addExistingUser($team->fresh(), $owner, $m3, TeamRole::Designer);
    }

    // --- Individual plan gate ---

    public function test_individual_plan_sees_upsell_instead_of_team_tab(): void
    {
        $user = $this->designer(['subscription_plan' => DesignerSubscription::PLAN_STANDARD]);

        $html = $this->actingAs($user)
            ->get(route('settings.index', ['tab' => 'team']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(__('subscription.team_feature_upsell_title'), $html);
    }

    public function test_individual_plan_cannot_invite_via_direct_post(): void
    {
        $user = $this->designer(['subscription_plan' => DesignerSubscription::PLAN_PRO]);
        $target = $this->designer(['email' => 'inv@example.com', 'subscription_plan' => null, 'subscription_ends_at' => null]);

        $this->actingAs($user)
            ->post(route('settings.team.invite'), [
                'email' => $target->email,
                'role' => TeamRole::Designer->value,
            ])
            ->assertSessionHasErrors('team');
    }

    // --- Downgrade guards ---

    public function test_cannot_downgrade_when_projects_exceed_new_limit(): void
    {
        $user = $this->designer(['subscription_plan' => DesignerSubscription::PLAN_STANDARD]); // 5 projects
        for ($i = 0; $i < 4; $i++) {
            $this->makeProject($user);
        }

        try {
            $this->limits()->assertCanSwitchTo($user, 'base'); // limit 2
            $this->fail('Downgrade must be blocked');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('plan', $e->errors());
        }

        // Data untouched
        $this->assertSame(4, Project::query()->where('user_id', $user->id)->count());
        // Downgrade to pro (unlimited) is allowed
        $this->limits()->assertCanSwitchTo($user->fresh(), 'pro');
    }

    public function test_cannot_downgrade_when_members_exceed_new_limit(): void
    {
        $owner = $this->designer(['subscription_plan' => DesignerSubscription::PLAN_PROGRESS]);
        $teams = app(TeamService::class);
        $team = $teams->activateCorporateForOwner($owner, null, SubscriptionPlan::findByKey('progress'));

        foreach (['d1@example.com', 'd2@example.com', 'd3@example.com'] as $email) {
            $member = $this->designer(['email' => $email, 'subscription_plan' => null, 'subscription_ends_at' => null]);
            $inv = $teams->addExistingUser($team->fresh(), $owner, $member, TeamRole::Designer);
            $teams->acceptInvitation($member, $inv);
        }

        $this->assertSame(4, $team->fresh()->activeMembersCount());

        // progress (7 seats) → economy (3 seats): blocked
        try {
            $this->limits()->assertCanSwitchTo($owner, 'economy');
            $this->fail('Seat downgrade must be blocked');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('plan', $e->errors());
        }

        // corporate → individual with members: blocked
        try {
            $this->limits()->assertCanSwitchTo($owner, 'pro');
            $this->fail('Corporate to individual with members must be blocked');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('plan', $e->errors());
        }

        // Nobody was removed automatically
        $this->assertSame(4, $team->fresh()->activeMembersCount());
    }

    public function test_corporate_to_individual_allowed_for_lonely_owner(): void
    {
        $owner = $this->designer(['subscription_plan' => DesignerSubscription::PLAN_PROGRESS]);
        app(TeamService::class)
            ->activateCorporateForOwner($owner, null, SubscriptionPlan::findByKey('progress'));

        $this->limits()->assertCanSwitchTo($owner, 'pro'); // no exception
        $this->assertTrue(true);
    }

    // --- Subscription page rendering ---

    public function test_subscription_page_renders_grouped_plans_with_billing_toggle(): void
    {
        $user = $this->designer(['subscription_plan' => DesignerSubscription::PLAN_STANDARD]);

        $html = $this->actingAs($user)
            ->get(route('subscription.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(__('subscription.group_individual'), $html);
        $this->assertStringContainsString(__('subscription.group_corporate'), $html);
        $this->assertStringContainsString('data-billing-switch', $html);
        $this->assertStringContainsString('data-price-yearly', $html);
        $this->assertStringContainsString(__('subscription.current_plan_badge'), $html);
        // No legacy plan keys may leak into the page
        $this->assertStringNotContainsString('plan_corporate', $html);
    }

    public function test_onboarding_page_renders_both_plan_groups(): void
    {
        $user = User::factory()->create(['account_type' => 'designer']);

        $html = $this->actingAs($user)
            ->get(route('subscription.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(__('subscription.group_individual'), $html);
        $this->assertStringContainsString(__('subscription.group_corporate'), $html);
        $this->assertStringContainsString('data-billing-switch', $html);
        $this->assertStringContainsString('data-checkout-url', $html);
    }

    // --- Annual pricing math ---

    public function test_annual_prices_and_discounts_are_consistent(): void
    {
        $cases = [
            'base' => [9990, 5, 113886],
            'standard' => [13500, 10, 145800],
            'pro' => [17500, 15, 178500],
            'economy' => [25000, 5, 285000],
            'progress' => [35000, 10, 378000],
            'success' => [50000, 15, 510000],
        ];

        $catalog = app(PlanCatalog::class);
        $this->assertSame(array_keys($cases), $catalog->all()->pluck('key')->all());

        foreach ($cases as $key => [$monthly, $discount, $annual]) {
            $plan = $catalog->find($key);
            $this->assertNotNull($plan, "Plan {$key} missing");
            $this->assertSame($monthly, $plan->monthlyPriceInt());
            $this->assertSame($discount, (int) $plan->annual_discount_percent);
            $this->assertSame($annual, $plan->annualPriceInt());
            // annual = monthly * 12 * (1 - discount/100), rounded
            $expected = (int) round($monthly * 12 * (1 - $discount / 100));
            $this->assertSame($expected, $annual, "Annual formula mismatch for {$key}");
            $this->assertSame($monthly * 12 - $annual, $plan->yearlySavings());
            $this->assertSame((int) round($annual / 12), $plan->annualMonthlyEquivalent());
        }
    }
}
