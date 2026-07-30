<?php

namespace App\Support;

use App\Models\User;

/**
 * Account ownership and extensible permission points.
 * Today every designer user is the account owner of their own data (user_id scope).
 * Later: team members / roles can plug into these methods without rewriting callers.
 */
class AccountPermissions
{
    public static function isAccountOwner(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        // Extensible: when team roles exist, check ownership / role here.
        return ($user->role ?? null) === 'designer';
    }

    public static function canManageProjectPipeline(?User $user): bool
    {
        return self::isAccountOwner($user);
    }

    public static function canManageSupplyPipeline(?User $user): bool
    {
        return self::isAccountOwner($user);
    }

    public static function ownsResource(?User $user, ?int $ownerUserId): bool
    {
        if (! $user || $ownerUserId === null) {
            return false;
        }

        return (int) $user->id === (int) $ownerUserId;
    }
}
