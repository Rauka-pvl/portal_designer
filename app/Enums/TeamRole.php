<?php

namespace App\Enums;

enum TeamRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Designer = 'designer';

    public function label(): string
    {
        return match ($this) {
            self::Owner => __('team.role_owner'),
            self::Admin => __('team.role_admin'),
            self::Designer => __('team.role_designer'),
        };
    }

    public function canManageBilling(): bool
    {
        return $this === self::Owner;
    }

    public function canManageMembers(): bool
    {
        return $this === self::Owner || $this === self::Admin;
    }

    public function canAssignRoles(): bool
    {
        return $this === self::Owner || $this === self::Admin;
    }

    public function seesAllTeamTasks(): bool
    {
        return $this === self::Owner || $this === self::Admin;
    }

    /** @return list<string> */
    public static function assignableBy(self $actor): array
    {
        return match ($actor) {
            self::Owner => [self::Admin->value, self::Designer->value],
            self::Admin => [self::Designer->value],
            self::Designer => [],
        };
    }
}
