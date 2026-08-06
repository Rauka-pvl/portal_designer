<?php

namespace App\Enums;

enum SupportTicketStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case WaitingForUser = 'waiting_for_user';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return __('support.status_'.$this->value);
    }

    /** Open tickets sort above resolved/closed. */
    public function isOpen(): bool
    {
        return ! in_array($this, [self::Resolved, self::Closed], true);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}
