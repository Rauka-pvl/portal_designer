<?php

namespace App\Enums;

enum TeamMemberStatus: string
{
    case Active = 'active';
    case Blocked = 'blocked';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active => __('team.status_active'),
            self::Blocked => __('team.status_blocked'),
            self::Inactive => __('team.status_inactive'),
        };
    }
}
