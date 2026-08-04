<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;
use App\Services\Team\TeamService;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Client $client): bool
    {
        $team = app(TeamService::class)->activeTeamFor($user);
        if ($team && app(TeamService::class)->teamHasCorporateAccess($team)) {
            return (int) $client->user_id === (int) $user->id
                || (int) $client->user_id === (int) $team->owner_id;
        }

        return (int) $client->user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Client $client): bool
    {
        return $this->view($user, $client);
    }

    public function delete(User $user, Client $client): bool
    {
        return $this->view($user, $client);
    }
}
