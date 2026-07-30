<?php

namespace App\Services\Team;

use App\Enums\TeamMemberStatus;
use App\Enums\TeamRole;
use App\Models\DesignerTeam;
use App\Models\DesignerTeamInvitation;
use App\Models\DesignerTeamMember;
use App\Models\Project;
use App\Models\User;
use App\Models\UserNotification;
use App\Support\DesignerSubscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeamService
{
    public function activeTeamFor(User $user): ?DesignerTeam
    {
        $membership = DesignerTeamMember::query()
            ->where('user_id', $user->id)
            ->where('status', TeamMemberStatus::Active->value)
            ->with('team')
            ->first();

        if (! $membership?->team || ! $membership->team->isActive()) {
            return null;
        }

        return $membership->team;
    }

    public function roleInActiveTeam(User $user): ?TeamRole
    {
        $team = $this->activeTeamFor($user);

        return $team?->roleFor($user);
    }

    public function isCorporateUser(User $user): bool
    {
        $team = $this->activeTeamFor($user);
        if (! $team) {
            return false;
        }

        return $this->teamHasCorporateAccess($team);
    }

    public function teamHasCorporateAccess(DesignerTeam $team): bool
    {
        if (! $team->isActive()) {
            return false;
        }

        $owner = $team->owner;
        if (! $owner) {
            return false;
        }

        if ((string) $owner->subscription_plan !== DesignerSubscription::PLAN_CORPORATE) {
            return false;
        }

        return DesignerSubscription::hasPersonalAccess($owner);
    }

    /**
     * Activate Corporate workspace after successful checkout for the paying owner.
     */
    public function activateCorporateForOwner(User $owner, ?string $teamName = null): DesignerTeam
    {
        return DB::transaction(function () use ($owner, $teamName) {
            $existing = DesignerTeam::query()
                ->where('owner_id', $owner->id)
                ->where('status', 'active')
                ->first();

            if ($existing) {
                $this->ensureOwnerMembership($existing, $owner);
                $this->attachOwnerProjects($existing, $owner);

                return $existing->fresh(['members', 'owner']);
            }

            // Soft-archive any previous inactive teams owned by this user (keep history).
            DesignerTeam::query()
                ->where('owner_id', $owner->id)
                ->where('status', '!=', 'archived')
                ->update(['status' => 'inactive']);

            $team = DesignerTeam::query()->create([
                'owner_id' => $owner->id,
                'name' => $teamName ?: (__('team.default_name', ['name' => $owner->name])),
                'status' => 'active',
                'max_members' => 5,
            ]);

            $this->ensureOwnerMembership($team, $owner);
            $this->attachOwnerProjects($team, $owner);

            return $team->fresh(['members', 'owner']);
        });
    }

    public function ensureOwnerMembership(DesignerTeam $team, User $owner): void
    {
        DesignerTeamMember::query()->updateOrCreate(
            ['team_id' => $team->id, 'user_id' => $owner->id],
            [
                'role' => TeamRole::Owner->value,
                'status' => TeamMemberStatus::Active->value,
                'joined_at' => now(),
                'invited_by' => null,
            ]
        );
    }

    public function attachOwnerProjects(DesignerTeam $team, User $owner): void
    {
        Project::query()
            ->where('user_id', $owner->id)
            ->whereNull('team_id')
            ->update(['team_id' => $team->id]);
    }

    public function assertCanManageMembers(User $actor, DesignerTeam $team): void
    {
        $role = $team->roleFor($actor);
        if (! $role || ! $role->canManageMembers()) {
            throw ValidationException::withMessages([
                'team' => [__('team.forbidden_manage_members')],
            ]);
        }
    }

    public function assertSeatAvailable(DesignerTeam $team): void
    {
        if (! $team->hasSeatAvailable()) {
            throw ValidationException::withMessages([
                'team' => [__('team.seat_limit_reached')],
            ]);
        }
    }

    public function assertUserNotInAnotherTeam(User $user, ?int $exceptTeamId = null): void
    {
        $q = DesignerTeamMember::query()
            ->where('user_id', $user->id)
            ->where('status', TeamMemberStatus::Active->value)
            ->whereHas('team', fn ($t) => $t->where('status', 'active'));

        if ($exceptTeamId) {
            $q->where('team_id', '!=', $exceptTeamId);
        }

        if ($q->exists()) {
            throw ValidationException::withMessages([
                'user_id' => [__('team.user_already_in_team')],
            ]);
        }
    }

    public function addExistingUser(DesignerTeam $team, User $actor, User $target, TeamRole $role): DesignerTeamMember
    {
        return DB::transaction(function () use ($team, $actor, $target, $role) {
            $this->assertCanManageMembers($actor, $team);
            $team = $team->fresh();
            $this->assertSeatAvailable($team);
            $this->assertUserNotInAnotherTeam($target, (int) $team->id);

            if ($role === TeamRole::Owner) {
                throw ValidationException::withMessages([
                    'role' => [__('team.cannot_assign_owner')],
                ]);
            }

            $actorRole = $team->roleFor($actor);
            if (! $actorRole || ! in_array($role->value, TeamRole::assignableBy($actorRole), true)) {
                throw ValidationException::withMessages([
                    'role' => [__('team.forbidden_assign_role')],
                ]);
            }

            if ($team->memberFor($target)?->isActive()) {
                throw ValidationException::withMessages([
                    'user_id' => [__('team.already_member')],
                ]);
            }

            $member = DesignerTeamMember::query()->updateOrCreate(
                ['team_id' => $team->id, 'user_id' => $target->id],
                [
                    'role' => $role->value,
                    'status' => TeamMemberStatus::Active->value,
                    'joined_at' => now(),
                    'invited_by' => $actor->id,
                ]
            );

            UserNotification::query()->create([
                'user_id' => $target->id,
                'title' => __('team.notify_added_title'),
                'comment' => __('team.notify_added_body', ['team' => $team->name, 'role' => $role->label()]),
                'action_key' => 'team_added',
                'is_read' => false,
            ]);

            return $member;
        });
    }

    public function inviteByEmail(DesignerTeam $team, User $actor, string $email, TeamRole $role): DesignerTeamInvitation
    {
        return DB::transaction(function () use ($team, $actor, $email, $role) {
            $this->assertCanManageMembers($actor, $team);
            $team = $team->fresh();
            $this->assertSeatAvailable($team);

            if ($role === TeamRole::Owner) {
                throw ValidationException::withMessages([
                    'role' => [__('team.cannot_assign_owner')],
                ]);
            }

            $actorRole = $team->roleFor($actor);
            if (! $actorRole || ! in_array($role->value, TeamRole::assignableBy($actorRole), true)) {
                throw ValidationException::withMessages([
                    'role' => [__('team.forbidden_assign_role')],
                ]);
            }

            $email = mb_strtolower(trim($email));
            $existingUser = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
            if ($existingUser) {
                $this->assertUserNotInAnotherTeam($existingUser, (int) $team->id);
            }

            $pending = DesignerTeamInvitation::query()
                ->where('team_id', $team->id)
                ->whereRaw('LOWER(email) = ?', [$email])
                ->where('status', 'pending')
                ->first();

            if ($pending?->isPending()) {
                throw ValidationException::withMessages([
                    'email' => [__('team.invite_already_pending')],
                ]);
            }

            return DesignerTeamInvitation::query()->create([
                'team_id' => $team->id,
                'email' => $email,
                'role' => $role->value,
                'token' => DesignerTeamInvitation::makeToken(),
                'status' => 'pending',
                'invited_by' => $actor->id,
                'expires_at' => now()->addDays(14),
            ]);
        });
    }

    public function removeMember(DesignerTeam $team, User $actor, DesignerTeamMember $member): void
    {
        DB::transaction(function () use ($team, $actor, $member) {
            $this->assertCanManageMembers($actor, $team);

            if ($member->team_id !== $team->id) {
                throw ValidationException::withMessages(['member' => [__('team.member_not_found')]]);
            }

            if ($member->role === TeamRole::Owner) {
                throw ValidationException::withMessages(['member' => [__('team.cannot_remove_owner')]]);
            }

            $actorRole = $team->roleFor($actor);
            if ($actorRole === TeamRole::Admin && $member->role === TeamRole::Admin) {
                throw ValidationException::withMessages(['member' => [__('team.forbidden_manage_members')]]);
            }

            $member->update(['status' => TeamMemberStatus::Inactive->value]);
        });
    }

    public function changeRole(DesignerTeam $team, User $actor, DesignerTeamMember $member, TeamRole $role): void
    {
        DB::transaction(function () use ($team, $actor, $member, $role) {
            $this->assertCanManageMembers($actor, $team);

            $currentRole = $member->role instanceof TeamRole
                ? $member->role
                : TeamRole::tryFrom((string) $member->role);

            if ($currentRole === TeamRole::Owner || $role === TeamRole::Owner) {
                throw ValidationException::withMessages(['role' => [__('team.cannot_assign_owner')]]);
            }

            $actorRole = $team->roleFor($actor);
            if (! $actorRole || ! in_array($role->value, TeamRole::assignableBy($actorRole), true)) {
                throw ValidationException::withMessages(['role' => [__('team.forbidden_assign_role')]]);
            }

            $member->update(['role' => $role->value]);

            UserNotification::query()->create([
                'user_id' => $member->user_id,
                'title' => __('team.notify_role_title'),
                'comment' => __('team.notify_role_body', ['role' => $role->label()]),
                'action_key' => 'team_role_changed',
                'is_read' => false,
            ]);
        });
    }

    /** @return list<array{id:int,name:string,email:string,role:string,role_label:string}> */
    public function assigneeOptions(User $user): array
    {
        if (! $this->isCorporateUser($user)) {
            return [[
                'id' => (int) $user->id,
                'name' => (string) $user->name,
                'email' => (string) $user->email,
                'role' => 'self',
                'role_label' => __('team.role_self'),
            ]];
        }

        $team = $this->activeTeamFor($user);
        if (! $team) {
            return [];
        }

        return $team->members()
            ->where('status', TeamMemberStatus::Active->value)
            ->with('user')
            ->get()
            ->filter(fn (DesignerTeamMember $m) => $m->user)
            ->map(fn (DesignerTeamMember $m) => [
                'id' => (int) $m->user_id,
                'name' => (string) $m->user->name,
                'email' => (string) $m->user->email,
                'role' => $m->role instanceof TeamRole ? $m->role->value : (string) $m->role,
                'role_label' => $m->role instanceof TeamRole ? $m->role->label() : (string) $m->role,
            ])
            ->values()
            ->all();
    }

    public function assertAssigneeAllowed(User $actor, ?int $assigneeId): int
    {
        $assigneeId = (int) ($assigneeId ?: $actor->id);

        if (! $this->isCorporateUser($actor)) {
            if ($assigneeId !== (int) $actor->id) {
                throw ValidationException::withMessages([
                    'responsible_id' => [__('team.personal_assignee_only_self')],
                ]);
            }

            return (int) $actor->id;
        }

        $team = $this->activeTeamFor($actor);
        $member = $team?->members()
            ->where('user_id', $assigneeId)
            ->where('status', TeamMemberStatus::Active->value)
            ->first();

        if (! $member) {
            throw ValidationException::withMessages([
                'responsible_id' => [__('team.assignee_not_in_team')],
            ]);
        }

        return $assigneeId;
    }
}
