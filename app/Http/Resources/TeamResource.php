<?php

namespace App\Http\Resources;

use App\Support\AccountPermissions;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status,
            'owner_id' => $this->owner_id,
            'max_members' => (int) $this->max_members,
            'seats_used' => $this->usedSeats(),
            'seats_remaining' => $this->seatsRemaining(),
            'can_manage_billing' => AccountPermissions::canManageBilling($request->user()),
        ];
    }
}
