<?php

namespace App\Policies;

use App\Enums\TeamRole;
use App\Models\SupportTicket;
use App\Models\User;

class SupportTicketPolicy
{
    public static function isStaff(User $user): bool
    {
        return in_array((string) ($user->role ?? ''), ['moderator', 'admin', 'system_admin'], true);
    }

    public function viewAny(User $user): bool
    {
        return self::isStaff($user) || (string) ($user->role ?? '') === 'designer';
    }

    public function create(User $user): bool
    {
        return (string) ($user->role ?? '') === 'designer';
    }

    public function view(User $user, SupportTicket $ticket): bool
    {
        if (self::isStaff($user)) {
            return true;
        }

        if ((int) $ticket->created_by === (int) $user->id) {
            return true;
        }

        // Corporate owner/admin sees all tickets of their team.
        return $this->isTeamManager($user, $ticket);
    }

    public function reply(User $user, SupportTicket $ticket): bool
    {
        if (! $this->view($user, $ticket)) {
            return false;
        }

        return ! $ticket->statusEnum()->isOpen() ? self::isStaff($user) : true;
    }

    public function updateStatus(User $user, SupportTicket $ticket): bool
    {
        return self::isStaff($user);
    }

    private function isTeamManager(User $user, SupportTicket $ticket): bool
    {
        if (! $ticket->team_id) {
            return false;
        }

        $team = $ticket->team ?? $ticket->load('team')->team;
        if (! $team || ! $team->isActive()) {
            return false;
        }

        $role = $team->roleFor($user);

        return in_array($role, [TeamRole::Owner, TeamRole::Admin], true);
    }
}
