<?php

namespace App\Support;

use App\Enums\TeamRole;
use App\Models\Project;
use App\Models\User;
use App\Services\Team\TeamService;

/**
 * Account ownership and Corporate team permission points.
 */
class AccountPermissions
{
    public static function isAccountOwner(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if (($user->role ?? null) !== 'designer') {
            return false;
        }

        $role = app(TeamService::class)->roleInActiveTeam($user);
        if ($role) {
            return $role === TeamRole::Owner || $role === TeamRole::Admin;
        }

        return true;
    }

    public static function canManageProjectPipeline(?User $user): bool
    {
        return self::isAccountOwner($user);
    }

    public static function canManageSupplyPipeline(?User $user): bool
    {
        return self::isAccountOwner($user);
    }

    public static function canManageClientPipeline(?User $user): bool
    {
        return self::isAccountOwner($user);
    }

    public static function ownsResource(?User $user, ?int $ownerUserId): bool
    {
        if (! $user || $ownerUserId === null) {
            return false;
        }

        if ((int) $user->id === (int) $ownerUserId) {
            return true;
        }

        // Same Corporate team as the resource owner.
        $teams = app(TeamService::class);
        $actorTeam = $teams->activeTeamFor($user);
        $owner = User::query()->find($ownerUserId);
        if (! $actorTeam || ! $owner || ! $teams->teamHasCorporateAccess($actorTeam)) {
            return false;
        }

        $ownerTeam = $teams->activeTeamFor($owner);

        return $ownerTeam && (int) $ownerTeam->id === (int) $actorTeam->id;
    }

    public static function canAccessProject(?User $user, Project $project): bool
    {
        if (! $user) {
            return false;
        }

        return WorkspaceAccess::canAccessProject($user, $project);
    }

    public static function canManageBilling(?User $user): bool
    {
        if (! $user || ($user->role ?? null) !== 'designer') {
            return false;
        }

        $role = app(TeamService::class)->roleInActiveTeam($user);
        if ($role) {
            return $role->canManageBilling();
        }

        return true;
    }
}
