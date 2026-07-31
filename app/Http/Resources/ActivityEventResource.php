<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'event_type' => $this->event_type,
            'body' => $this->body,
            'meta' => $this->meta ?? [],
            'actor' => $this->actor ? ['id' => (int) $this->actor_id, 'name' => $this->actor->name] : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
