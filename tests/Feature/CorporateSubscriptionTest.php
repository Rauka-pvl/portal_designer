<?php

namespace Tests\Feature;

use App\Enums\TeamMemberStatus;
use App\Enums\TeamRole;
use App\Models\DesignerTeam;
use App\Models\DesignerTeamInvitation;
use App\Models\DesignerTeamMember;
use App\Models\Project;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Team\TeamService;
use App\Support\DesignerSubscription;
use App\Support\WorkspaceAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CorporateSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['subscription.allow_stub_payments' => true]);
        config(['subscription.promo_code' => '']);
    }

    private function designer(array $attrs = []): User
    {
        $user = User::factory()->create(array_merge([
            'account_type' => 'designer',
        ], $attrs));

        $planKey = $attrs['subscription_plan'] ?? DesignerSubscription::PLAN_PRO;
        if ($planKey) {
            $plan = \App\Models\SubscriptionPlan::findByKey($planKey);
            if ($plan) {
                \App\Models\Subscription::create([
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

    private function inviteAndAccept(TeamService $teams, DesignerTeam $team, User $owner, User $member, TeamRole $role): DesignerTeamMember
    {
        $invitation = $teams->addExistingUser($team, $owner, $member, $role);

        return $teams->acceptInvitation($member, $invitation);
    }

    public function test_corporate_is_third_plan_with_price_and_seat_limit(): void
    {
        $plans = array_values(DesignerSubscription::plans());
        $this->assertCount(3, $plans);
        $this->assertSame('corporate', $plans[2]['key']);
        $this->assertSame(29990, (int) $plans[2]['price']);
        $this->assertSame(5, (int) $plans[2]['max_members']);
    }

    public function test_activating_corporate_creates_team_and_owner_seat(): void
    {
        $owner = $this->designer(['subscription_plan' => DesignerSubscription::PLAN_STANDARD]);
        Project::query()->create([
            'user_id' => $owner->id,
            'name' => 'Studio A',
            'status' => 'lead',
            'start_date' => now()->toDateString(),
            'planned_end_date' => now()->addMonth()->toDateString(),
        ]);

        DesignerSubscription::checkout(
            $owner,
            DesignerSubscription::PLAN_CORPORATE,
            DesignerSubscription::METHOD_KASPI
        );

        $owner->refresh();
        $this->assertSame(DesignerSubscription::PLAN_CORPORATE, $owner->subscription_plan);

        $team = DesignerTeam::query()->where('owner_id', $owner->id)->where('status', 'active')->first();
        $this->assertNotNull($team);
        $this->assertSame(1, $team->activeMembersCount());
        $this->assertSame(1, $team->usedSeats());
        $this->assertTrue(
            Project::query()->where('user_id', $owner->id)->where('team_id', $team->id)->exists()
        );
    }

    public function test_cannot_exceed_five_seats_including_pending_invites(): void
    {
        $owner = $this->designer();
        $teams = app(TeamService::class);
        $team = $teams->activateCorporateForOwner($owner);
        $owner->subscription_plan = DesignerSubscription::PLAN_CORPORATE;
        $owner->subscription_ends_at = now()->addMonth();
        $owner->save();

        for ($i = 0; $i < 4; $i++) {
            $member = $this->designer([
                'email' => "m{$i}@example.com",
                'subscription_ends_at' => null,
                'subscription_plan' => null,
            ]);
            $teams->addExistingUser($team, $owner, $member, TeamRole::Designer);
        }

        $this->assertSame(5, $team->fresh()->usedSeats());

        $this->expectException(ValidationException::class);
        $teams->inviteByEmail($team->fresh(), $owner, 'overflow@example.com', TeamRole::Designer);
    }

    public function test_user_cannot_belong_to_two_active_teams(): void
    {
        $ownerA = $this->designer(['email' => 'a@example.com']);
        $ownerB = $this->designer(['email' => 'b@example.com']);
        $member = $this->designer([
            'email' => 'shared@example.com',
            'subscription_ends_at' => null,
            'subscription_plan' => null,
        ]);

        $teams = app(TeamService::class);
        $teamA = $teams->activateCorporateForOwner($ownerA);
        $teamB = $teams->activateCorporateForOwner($ownerB);
        $ownerA->forceFill([
            'subscription_plan' => DesignerSubscription::PLAN_CORPORATE,
            'subscription_ends_at' => now()->addMonth(),
        ])->save();
        $ownerB->forceFill([
            'subscription_plan' => DesignerSubscription::PLAN_CORPORATE,
            'subscription_ends_at' => now()->addMonth(),
        ])->save();

        $this->inviteAndAccept($teams, $teamA, $ownerA, $member, TeamRole::Designer);

        $this->expectException(ValidationException::class);
        $teams->addExistingUser($teamB, $ownerB, $member, TeamRole::Designer);
    }

    public function test_personal_assignee_must_be_self(): void
    {
        $user = $this->designer();
        $other = $this->designer(['email' => 'other@example.com']);

        $this->expectException(ValidationException::class);
        app(TeamService::class)->assertAssigneeAllowed($user, (int) $other->id);
    }

    public function test_corporate_assignee_must_be_team_member(): void
    {
        $owner = $this->designer();
        $member = $this->designer([
            'email' => 'mate@example.com',
            'subscription_ends_at' => null,
            'subscription_plan' => null,
        ]);
        $outsider = $this->designer(['email' => 'out@example.com']);

        $teams = app(TeamService::class);
        $team = $teams->activateCorporateForOwner($owner);
        $owner->forceFill([
            'subscription_plan' => DesignerSubscription::PLAN_CORPORATE,
            'subscription_ends_at' => now()->addMonth(),
        ])->save();
        $invitation = $teams->addExistingUser($team, $owner, $member, TeamRole::Designer);

        try {
            $teams->assertAssigneeAllowed($owner, (int) $member->id);
            $this->fail('Pending invitee must not be assignable');
        } catch (ValidationException) {
            // expected before accept
        }

        $teams->acceptInvitation($member, $invitation);
        $this->assertSame((int) $member->id, $teams->assertAssigneeAllowed($owner, (int) $member->id));

        $this->expectException(ValidationException::class);
        $teams->assertAssigneeAllowed($owner, (int) $outsider->id);
    }

    public function test_designer_sees_only_own_or_assigned_tasks_scope(): void
    {
        $owner = $this->designer();
        $designer = $this->designer([
            'email' => 'des@example.com',
            'subscription_ends_at' => null,
            'subscription_plan' => null,
        ]);
        $teams = app(TeamService::class);
        $team = $teams->activateCorporateForOwner($owner);
        $owner->forceFill([
            'subscription_plan' => DesignerSubscription::PLAN_CORPORATE,
            'subscription_ends_at' => now()->addMonth(),
        ])->save();
        $this->inviteAndAccept($teams, $team, $owner, $designer, TeamRole::Designer);

        $project = Project::query()->create([
            'user_id' => $owner->id,
            'team_id' => $team->id,
            'name' => 'Shared',
            'status' => 'lead',
            'start_date' => now()->toDateString(),
            'planned_end_date' => now()->addMonth()->toDateString(),
        ]);

        $this->assertTrue(WorkspaceAccess::canAccessProject($designer, $project));
        $this->assertFalse(WorkspaceAccess::canSeeAllTeamTasks($designer));
        $this->assertTrue(WorkspaceAccess::canSeeAllTeamTasks($owner));
    }

    public function test_expired_corporate_blocks_cabinet_but_keeps_team(): void
    {
        $owner = $this->designer([
            'subscription_plan' => DesignerSubscription::PLAN_CORPORATE,
            'subscription_ends_at' => now()->subDay(),
            'subscription_trial_ends_at' => null,
        ]);
        $teams = app(TeamService::class);
        $team = $teams->activateCorporateForOwner($owner);

        $this->assertFalse(DesignerSubscription::hasAccess($owner));
        $this->assertTrue(DesignerTeam::query()->whereKey($team->id)->exists());

        $this->actingAs($owner)
            ->get(route('projects.index'))
            ->assertRedirect(route('subscription.index', ['reason' => 'corporate_expired']));

        $this->actingAs($owner)
            ->get(route('subscription.index'))
            ->assertOk();
    }

    public function test_settings_hides_notifications_and_subscriptions_tabs(): void
    {
        $user = $this->designer();

        $html = $this->actingAs($user)
            ->get(route('settings.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('route(\'subscription.index\')', $html);
        $this->assertStringContainsString('tab=team', $html);
        $this->assertStringContainsString('tab=roles', $html);
        $this->assertStringNotContainsString('settings-tab" disabled', $html);
    }

    public function test_create_member_uses_standard_password_not_temporary(): void
    {
        $owner = $this->designer();
        $teams = app(TeamService::class);
        $team = $teams->activateCorporateForOwner($owner);
        $owner->forceFill([
            'subscription_plan' => DesignerSubscription::PLAN_CORPORATE,
            'subscription_ends_at' => now()->addMonth(),
        ])->save();

        $this->actingAs($owner)
            ->post(route('settings.team.create'), [
                'name' => 'New Mate',
                'email' => 'newbie@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role' => TeamRole::Designer->value,
            ])
            ->assertRedirect(route('settings.index', ['tab' => 'team']));

        $created = User::query()->where('email', 'newbie@example.com')->first();
        $this->assertNotNull($created);
        $this->assertFalse((bool) ($created->must_change_password ?? false));
        $this->assertFalse(
            DesignerTeamMember::query()
                ->where('team_id', $team->id)
                ->where('user_id', $created->id)
                ->where('status', TeamMemberStatus::Active->value)
                ->exists()
        );
        $this->assertTrue(
            DesignerTeamInvitation::query()
                ->where('team_id', $team->id)
                ->whereRaw('LOWER(email) = ?', ['newbie@example.com'])
                ->where('status', 'pending')
                ->exists()
        );
        $this->assertTrue(
            UserNotification::query()
                ->where('user_id', $created->id)
                ->where('action_key', 'team_invited')
                ->exists()
        );
    }

    public function test_admin_cannot_remove_owner(): void
    {
        $owner = $this->designer();
        $admin = $this->designer([
            'email' => 'admin@example.com',
            'subscription_ends_at' => null,
            'subscription_plan' => null,
        ]);
        $teams = app(TeamService::class);
        $team = $teams->activateCorporateForOwner($owner);
        $owner->forceFill([
            'subscription_plan' => DesignerSubscription::PLAN_CORPORATE,
            'subscription_ends_at' => now()->addMonth(),
        ])->save();
        $this->inviteAndAccept($teams, $team, $owner, $admin, TeamRole::Admin);

        $ownerMember = $team->memberFor($owner);
        $this->expectException(ValidationException::class);
        $teams->removeMember($team, $admin, $ownerMember);
    }

    public function test_invite_notifies_and_accept_grants_access(): void
    {
        $owner = $this->designer();
        $invitee = $this->designer([
            'email' => 'invitee@example.com',
            'subscription_ends_at' => null,
            'subscription_plan' => null,
        ]);
        $teams = app(TeamService::class);
        $team = $teams->activateCorporateForOwner($owner);
        $owner->forceFill([
            'subscription_plan' => DesignerSubscription::PLAN_CORPORATE,
            'subscription_ends_at' => now()->addMonth(),
        ])->save();

        $invitation = $teams->inviteByEmail($team, $owner, $invitee->email, TeamRole::Designer);

        $this->assertSame('pending', $invitation->status);
        $this->assertNull($teams->activeTeamFor($invitee));
        $notification = UserNotification::query()
            ->where('user_id', $invitee->id)
            ->where('action_key', 'team_invited')
            ->where('related_invitation_id', $invitation->id)
            ->first();
        $this->assertNotNull($notification);

        $this->actingAs($invitee)
            ->post(route('notifications.team_invite_accept', $notification->id))
            ->assertRedirect();

        $invitee->refresh();
        $this->assertSame((int) $team->id, (int) $teams->activeTeamFor($invitee)?->id);
        $this->assertSame('accepted', $invitation->fresh()->status);
        $this->assertSame(DesignerSubscription::PLAN_CORPORATE, $invitee->subscription_plan);
        $this->assertTrue(DesignerSubscription::hasAccess($invitee));
        $this->assertContains(
            (int) $invitee->id,
            collect($teams->assigneeOptions($owner))->pluck('id')->all()
        );
    }

    public function test_remove_member_clears_inherited_corporate_plan(): void
    {
        $owner = $this->designer();
        $member = $this->designer([
            'email' => 'seat@example.com',
            'subscription_ends_at' => null,
            'subscription_plan' => null,
        ]);
        $teams = app(TeamService::class);
        $team = $teams->activateCorporateForOwner($owner);
        $owner->forceFill([
            'subscription_plan' => DesignerSubscription::PLAN_CORPORATE,
            'subscription_ends_at' => now()->addMonth(),
        ])->save();

        $this->inviteAndAccept($teams, $team, $owner, $member, TeamRole::Designer);
        $this->assertSame(DesignerSubscription::PLAN_CORPORATE, $member->fresh()->subscription_plan);

        $seat = $team->memberFor($member);
        $this->assertNotNull($seat);
        $teams->removeMember($team, $owner, $seat);

        $member->refresh();
        $this->assertNull($member->subscription_plan);
        $this->assertNull($teams->activeTeamFor($member));
        $this->assertFalse(DesignerSubscription::hasAccess($member));
    }

    public function test_decline_invitation_frees_seat(): void
    {
        $owner = $this->designer();
        $invitee = $this->designer([
            'email' => 'decline@example.com',
            'subscription_ends_at' => null,
            'subscription_plan' => null,
        ]);
        $teams = app(TeamService::class);
        $team = $teams->activateCorporateForOwner($owner);
        $owner->forceFill([
            'subscription_plan' => DesignerSubscription::PLAN_CORPORATE,
            'subscription_ends_at' => now()->addMonth(),
        ])->save();

        $invitation = $teams->inviteByEmail($team, $owner, $invitee->email, TeamRole::Designer);
        $this->assertSame(2, $team->fresh()->usedSeats());

        Sanctum::actingAs($invitee);
        $this->postJson('/api/team/invitations/'.$invitation->id.'/decline')
            ->assertOk();

        $this->assertSame('cancelled', $invitation->fresh()->status);
        $this->assertSame(1, $team->fresh()->usedSeats());
        $this->assertNull($teams->activeTeamFor($invitee));
    }
}
