<?php

namespace App\Services\Billing;

use App\Exceptions\PlanLimitExceeded;
use App\Models\DesignerTeam;
use App\Models\Project;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Team\TeamService;
use Illuminate\Validation\ValidationException;

/**
 * Centralized plan limits: projects, team seats, priority support, downgrades.
 * All limit checks must go through this service — never hardcode plan keys
 * or limits in controllers/views.
 */
class PlanLimitService
{
    public function __construct(
        private readonly TeamService $teams,
        private readonly PlanCatalog $catalog,
    ) {}

    /** The user whose subscription pays the bills (self or corporate team owner). */
    public function billingOwner(User $user): User
    {
        $team = $this->teams->activeTeamFor($user);
        if ($team && $this->teams->teamHasCorporateAccess($team) && (int) $team->owner_id !== (int) $user->id) {
            return $team->owner ?: $user;
        }

        return $user;
    }

    /** Effective plan of the billing owner (seat members inherit owner's plan). */
    public function currentPlanFor(User $user): ?SubscriptionPlan
    {
        $owner = $this->billingOwner($user);
        $plan = $owner->subscription?->plan;

        if ($plan && $plan->status === 'active' && $plan->is_active) {
            return $plan;
        }

        // Archived legacy plan still governs access until subscription is cleaned up.
        return $plan;
    }

    /** null = unlimited, null plan = no subscription (treated as unlimited to avoid false blocks pre-access). */
    public function projectLimitFor(User $user): ?int
    {
        return $this->currentPlanFor($user)?->max_projects;
    }

    /**
     * Individual: all non-deleted projects owned by the subscription owner.
     * Corporate: all non-deleted projects of the whole team (any status/stage).
     */
    public function projectCountFor(User $user): int
    {
        $team = $this->teams->activeTeamFor($user);
        if ($team && $this->teams->teamHasCorporateAccess($team)) {
            return $this->projectCountForTeam($team);
        }

        return Project::query()->where('user_id', $user->id)->count();
    }

    public function projectCountForTeam(DesignerTeam $team): int
    {
        $memberIds = $team->members()
            ->where('status', \App\Enums\TeamMemberStatus::Active->value)
            ->pluck('user_id');

        return Project::query()
            ->where(function ($q) use ($team, $memberIds) {
                $q->where('team_id', $team->id)
                    ->orWhere(function ($personal) use ($memberIds) {
                        $personal->whereIn('user_id', $memberIds)->whereNull('team_id');
                    });
            })
            ->count();
    }

    public function canCreateProject(User $user): bool
    {
        $limit = $this->projectLimitFor($user);
        if ($limit === null) {
            return true;
        }

        return $this->projectCountFor($user) < $limit;
    }

    /** @throws PlanLimitExceeded code PROJECT_LIMIT_REACHED */
    public function assertCanCreateProject(User $user): void
    {
        if ($this->canCreateProject($user)) {
            return;
        }

        throw new PlanLimitExceeded(
            errorCode: 'PROJECT_LIMIT_REACHED',
            limit: $this->projectLimitFor($user),
            current: $this->projectCountFor($user),
            message: __('subscription.project_limit_reached', [
                'limit' => $this->projectLimitFor($user),
                'current' => $this->projectCountFor($user),
            ]),
        );
    }

    /** Effective seat limit for the team: owner plan wins, team column as fallback. null = unlimited. */
    public function seatLimitFor(DesignerTeam $team): ?int
    {
        $plan = $team->owner?->subscription?->plan;

        return $plan?->max_users ?? $team->max_members;
    }

    public function hasPrioritySupport(User $user): bool
    {
        return (bool) $this->currentPlanFor($user)?->priority_support;
    }

    /**
     * Backend guard for plan switches. Blocks downgrades that would exceed the
     * new plan's project or user limits — never deletes user data.
     *
     * @throws ValidationException with 'plan' messages describing required actions
     */
    public function assertCanSwitchTo(User $user, string $newPlanKey): void
    {
        $newPlan = $this->catalog->find($newPlanKey);
        if (! $newPlan) {
            throw ValidationException::withMessages([
                'plan' => [__('subscription.invalid_plan')],
            ]);
        }

        $current = $this->currentPlanFor($user);
        if ($current && $current->key === $newPlan->key) {
            return;
        }

        // --- Projects ---
        if (! $newPlan->unlimitedProjects()) {
            $projects = $this->projectCountFor($user);
            if ($projects > (int) $newPlan->max_projects) {
                throw ValidationException::withMessages([
                    'plan' => [__('subscription.downgrade_projects_exceeded', [
                        'current' => $projects,
                        'limit' => (int) $newPlan->max_projects,
                        'plan' => $newPlan->name,
                    ])],
                ]);
            }
        }

        $team = $this->teams->activeTeamFor($user);
        $ownsTeam = $team && (int) $team->owner_id === (int) $user->id;
        $activeMembers = $ownsTeam ? $team->activeMembersCount() : 0;

        if ($newPlan->isIndividual()) {
            // Corporate → individual: no other members may remain on the team.
            if ($ownsTeam && $activeMembers > 1) {
                throw ValidationException::withMessages([
                    'plan' => [__('subscription.downgrade_members_exceeded', [
                        'current' => $activeMembers,
                        'limit' => 1,
                        'plan' => $newPlan->name,
                    ])],
                ]);
            }

            return;
        }

        // Corporate → corporate: team must fit the new seat limit.
        if (! $newPlan->unlimitedUsers() && $ownsTeam && $activeMembers > (int) $newPlan->max_users) {
            throw ValidationException::withMessages([
                'plan' => [__('subscription.downgrade_members_exceeded', [
                    'current' => $activeMembers,
                    'limit' => (int) $newPlan->max_users,
                    'plan' => $newPlan->name,
                ])],
            ]);
        }
    }
}
