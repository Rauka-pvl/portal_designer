<?php

namespace App\Enums;

enum ClientStatus: string
{
    case New = 'new';
    case InWork = 'in_work';
    case NotWorking = 'not_working';

    public function label(): string
    {
        return match ($this) {
            self::New => __('clients.new'),
            self::InWork => __('clients.in_work'),
            self::NotWorking => __('clients.not_working'),
        };
    }

    public function defaultColor(): string
    {
        return match ($this) {
            self::New => '#3b82f6',
            self::InWork => '#f59e0b',
            self::NotWorking => '#64748b',
        };
    }

    /**
     * @return list<self>
     */
    public static function funnelOrder(): array
    {
        return [self::New, self::InWork, self::NotWorking];
    }
}
