<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->user?->name,
            'email' => $this->user?->email,
            'role' => $this->role?->value ?? (string) $this->role,
            'status' => $this->status?->value ?? (string) $this->status,
            'joined_at' => $this->joined_at?->toISOString(),
        ];
    }
}
