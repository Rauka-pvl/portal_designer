<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\User */
class UserBriefResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $avatar = $this->avatar ?? $this->avatar_path ?? null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->when($this->email !== null, $this->email),
            'avatar' => $this->when($avatar !== null, Storage::disk('public')->url($avatar)),
        ];
    }
}
