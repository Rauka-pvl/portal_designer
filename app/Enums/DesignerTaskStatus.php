<?php

namespace App\Enums;

enum DesignerTaskStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New => __('tasks.status_new'),
            self::InProgress => __('tasks.status_in_progress'),
            self::Completed => __('tasks.status_completed'),
            self::Cancelled => __('tasks.status_cancelled'),
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** @return list<array{key:string,label:string}> */
    public static function columns(): array
    {
        return array_map(
            fn (self $s) => ['key' => $s->value, 'label' => $s->label()],
            self::cases()
        );
    }
}
