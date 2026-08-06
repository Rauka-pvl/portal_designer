<?php

namespace App\Enums;

enum SupportCategory: string
{
    case SiteError = 'site_error';
    case Payment = 'payment';
    case Subscription = 'subscription';
    case Projects = 'projects';
    case Tasks = 'tasks';
    case Supplies = 'supplies';
    case Team = 'team';
    case Improvement = 'improvement';
    case Other = 'other';

    public function label(): string
    {
        return __('support.category_'.$this->value);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }

    /** @return list<array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(
            fn (self $c) => ['value' => $c->value, 'label' => $c->label()],
            self::cases(),
        );
    }
}
