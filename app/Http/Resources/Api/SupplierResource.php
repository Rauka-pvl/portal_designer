<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id, 'user_id' => $this->user_id ? (int) $this->user_id : null,
            'name' => $this->name, 'email' => $this->email, 'phone' => $this->phone, 'telegram' => $this->telegram,
            'whatsapp' => $this->whatsapp, 'website' => $this->website, 'city' => $this->city, 'address' => $this->address,
            'sphere' => $this->sphere, 'brands' => array_values($this->brands ?? []), 'cities_presence' => array_values($this->cities_presence ?? []),
            'recommend' => (bool) $this->recommend, 'profile_status' => $this->profile_status, 'moderation_status' => $this->moderation_status,
            'logo_url' => $this->logo ? asset('storage/'.$this->logo) : null,
            'is_owned_by_designer' => (bool) ($this->is_owned_by_designer ?? false),
            'is_favorite' => (bool) ($this->is_favorite ?? false),
            'created_at' => $this->created_at?->toIso8601String(), 'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
