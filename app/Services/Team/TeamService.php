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

        $ownerSubscription = $owner->subscription;
        if (! $ownerSubscription || (string) $ownerSubscription->plan?->key !== DesignerSubscription::PLAN_CORPORATE) {
            return false;
        }

        if ($ownerSubscription->expires_at && $ownerSubscription->expires_at->isFuture()) {
            return true;
        }

        if ($ownerSubscription->trial_ends_at && $ownerSubscription->trial_ends_at->isFuture()) {
            return true;
        }

        return false;
    }

    /**
     * Activate Corporate workspace after successful checkout for the paying owner.
     */
    public function activateCorporateForOwner(User $owner, ?string $teamName = null): DesignerTeam
    {
        return DB::transaction(function () use ($owner, $teamName) {
            $plan = \App\Models\SubscriptionPlan::findByKey(DesignerSubscription::PLAN_CORPORATE);
            if ($plan) {
                $ownerSubscription = $owner->subscription;
                $isExpired = $ownerSubscription?->expires_at && $ownerSubscription->expires_at->isPast();

                if (! $isExpired) {
                    \App\Models\Subscription::query()->updateOrCreate(
                        ['user_id' => $owner->id],
                        [
                            'plan_id' => $plan->id,
                            'status' => 'active',
                            'starts_at' => now(),
                            'expires_at' => now()->addMonth(),
                            'trial_ends_at' => null,
                            'cancelled_at' => null,
                            'cancel_reason' => null,
                        ]
                    );
                    $owner->refresh();
                }
            }

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

    /**
     * Invite an existing designer — pending until they accept.
     * Kept for call-site compatibility; does not grant Active membership.
     */
    public function addExistingUser(DesignerTeam $team, User $actor, User $target, TeamRole $role): DesignerTeamInvitation
    {
        if (($target->role ?? null) !== 'designer') {
            throw ValidationException::withMessages([
                'email' => [__('team.user_not_designer')],
            ]);
        }

        return $this->inviteByEmail($team, $actor, (string) $target->email, $role);
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
                if ($team->memberFor($existingUser)?->isActive()) {
                    throw ValidationException::withMessages([
                        'email' => [__('team.already_member')],
                    ]);
                }
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

            if ($pending) {
                $pending->update(['status' => 'expired']);
            }

            $invitation = DesignerTeamInvitation::query()->create([
                'team_id' => $team->id,
                'email' => $email,
                'role' => $role->value,
                'token' => DesignerTeamInvitation::makeToken(),
                'status' => 'pending',
                'invited_by' => $actor->id,
                'expires_at' => now()->addDays(14),
            ]);

            $this->notifyInvitee($invitation->fresh('team'));

            return $invitation;
        });
    }

    public function acceptInvitation(User $user, DesignerTeamInvitation $invitation): DesignerTeamMember
    {
        return DB::transaction(function () use ($user, $invitation) {
            $invitation = DesignerTeamInvitation::query()->lockForUpdate()->findOrFail($invitation->id);

            if (! $invitation->isPending()) {
                throw ValidationException::withMessages([
                    'invitation' => [__('team.invite_not_found')],
                ]);
            }

            if (mb_strtolower((string) $user->email) !== mb_strtolower((string) $invitation->email)) {
                throw ValidationException::withMessages([
                    'invitation' => [__('team.invite_email_mismatch')],
                ]);
            }

            if (($user->role ?? null) !== 'designer') {
                throw ValidationException::withMessages([
                    'invitation' => [__('team.user_not_designer')],
                ]);
            }

            $team = $invitation->team;
            if (! $team || ! $team->isActive() || ! $this->teamHasCorporateAccess($team)) {
                throw ValidationException::withMessages([
                    'invitation' => [__('team.corporate_required')],
                ]);
            }

            $this->assertUserNotInAnotherTeam($user, (int) $team->id);

            if ($team->memberFor($user)?->isActive()) {
                $invitation->update([
                    'status' => 'accepted',
                    'accepted_at' => now(),
                ]);
                $this->applyCorporatePlanViaTeam($user, $team);
                $this->finalizeInviteNotifications($invitation, 'team_invite_accepted');

                return $team->memberFor($user);
            }

            $role = $invitation->role instanceof TeamRole
                ? $invitation->role
                : TeamRole::from((string) $invitation->role);

            $member = DesignerTeamMember::query()->updateOrCreate(
                ['team_id' => $team->id, 'user_id' => $user->id],
                [
                    'role' => $role->value,
                    'status' => TeamMemberStatus::Active->value,
                    'joined_at' => now(),
                    'invited_by' => $invitation->invited_by,
                ]
            );

            $invitation->update([
                'status' => 'accepted',
                'accepted_at' => now(),
            ]);

            $this->applyCorporatePlanViaTeam($user, $team);
            $this->finalizeInviteNotifications($invitation, 'team_invite_accepted');

            return $member->fresh('user');
        });
    }

    public function declineInvitation(User $user, DesignerTeamInvitation $invitation): void
    {
        DB::transaction(function () use ($user, $invitation) {
            $invitation = DesignerTeamInvitation::query()->lockForUpdate()->findOrFail($invitation->id);

            if (! $invitation->isPending()) {
                throw ValidationException::withMessages([
                    'invitation' => [__('team.invite_not_found')],
                ]);
            }

            if (mb_strtolower((string) $user->email) !== mb_strtolower((string) $invitation->email)) {
                throw ValidationException::withMessages([
                    'invitation' => [__('team.invite_email_mismatch')],
                ]);
            }

            $invitation->update(['status' => 'cancelled']);
            $this->finalizeInviteNotifications($invitation, 'team_invite_declined');
        });
    }

    public function resendInvitation(DesignerTeam $team, User $actor, DesignerTeamInvitation $invitation): DesignerTeamInvitation
    {
        return DB::transaction(function () use ($team, $actor, $invitation) {
            $this->assertCanManageMembers($actor, $team);

            if ((int) $invitation->team_id !== (int) $team->id) {
                throw ValidationException::withMessages([
                    'invitation' => [__('team.invite_not_found')],
                ]);
            }

            if ($invitation->status !== 'pending') {
                throw ValidationException::withMessages([
                    'invitation' => [__('team.invite_not_found')],
                ]);
            }

            $invitation->update([
                'token' => DesignerTeamInvitation::makeToken(),
                'expires_at' => now()->addDays(14),
            ]);

            $fresh = $invitation->fresh('team');
            $this->invalidateInviteActionNotifications($fresh);
            $this->notifyInvitee($fresh);

            return $fresh;
        });
    }

    public function notifyInvitee(DesignerTeamInvitation $invitation): void
    {
        $email = mb_strtolower((string) $invitation->email);
        $invitee = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('account_type', 'designer')
            ->first();

        if (! $invitee) {
            return;
        }

        $teamName = $invitation->team?->name ?? __('team.page_title');

        UserNotification::query()->create([
            'user_id' => $invitee->id,
            'title' => __('team.notify_invite_title'),
            'comment' => __('team.notify_invite_body', ['team' => $teamName]),
            'action_key' => 'team_invited',
            'related_invitation_id' => $invitation->id,
            'is_read' => false,
        ]);
    }

    private function invalidateInviteActionNotifications(DesignerTeamInvitation $invitation): void
    {
        UserNotification::query()
            ->where('related_invitation_id', $invitation->id)
            ->where('action_key', 'team_invited')
            ->update([
                'action_key' => null,
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    private function finalizeInviteNotifications(DesignerTeamInvitation $invitation, string $actionKey): void
    {
        UserNotification::query()
            ->where('related_invitation_id', $invitation->id)
            ->where('action_key', 'team_invited')
            ->update([
                'action_key' => $actionKey,
                'is_read' => true,
                'read_at' => now(),
            ]);
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
            $removedUser = User::query()->find($member->user_id);
            if ($removedUser) {
                $this->clearCorporatePlanViaTeam($removedUser);
            }
        });
    }

    /**
     * Seat members get Corporate plan label; billing stays on the owner.
     */
    public function applyCorporatePlanViaTeam(User $user, DesignerTeam $team): void
    {
        $owner = $team->owner;
        $plan = \App\Models\SubscriptionPlan::findByKey(DesignerSubscription::PLAN_CORPORATE);
        if ($plan) {
            \App\Models\Subscription::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'plan_id' => $plan->id,
                    'status' => 'active',
                    'starts_at' => now(),
                    'expires_at' => $owner?->subscription?->expires_at,
                    'trial_ends_at' => null,
                    'cancelled_at' => null,
                    'cancel_reason' => null,
                ]
            );
        }

        $user->forceFill([
            'subscription_plan' => DesignerSubscription::PLAN_CORPORATE,
            'subscription_cancelled_at' => null,
            'subscription_cancel_reason' => null,
            'subscription_trial_ends_at' => null,
            // Mirror owner period for UI; access for non-owners is still via team membership.
            'subscription_ends_at' => $owner?->subscription_ends_at,
        ])->save();
    }

    /**
     * When leaving a Corporate seat, drop inherited Corporate plan (not for team owners).
     */
    public function clearCorporatePlanViaTeam(User $user): void
    {
        $ownsActiveTeam = DesignerTeam::query()
            ->where('owner_id', $user->id)
            ->where('status', 'active')
            ->exists();

        if ($ownsActiveTeam) {
            return;
        }

        $subscription = $user->subscription;
        if (! $subscription || (string) $subscription->plan?->key !== DesignerSubscription::PLAN_CORPORATE) {
            return;
        }

        \App\Models\Subscription::query()
            ->where('user_id', $user->id)
            ->delete();

        $user->forceFill([
            'subscription_plan' => null,
            'subscription_ends_at' => null,
            'subscription_cancelled_at' => null,
            'subscription_cancel_reason' => null,
        ])->save();
        $user->refresh();
    }

    public function changeRole(DesignerTeam $team, User $actor, DesignerTeamMember $member, TeamRole $role): void
    {
        DB::transaction(function () use ($team, $actor, $member, $role) {
            $this->assertCanManageMembers($actor, $team);

            if ((int) $member->team_id !== (int) $team->id) {
                throw ValidationException::withMessages(['member' => [__('team.member_not_found')]]);
            }

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
