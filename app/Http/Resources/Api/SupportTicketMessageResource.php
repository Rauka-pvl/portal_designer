<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\SupportTicketMessage */
class SupportTicketMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_id' => $this->ticket_id,
            'sender_role' => $this->sender_role,
            'message' => $this->message,
            'is_system' => (bool) $this->is_system,
            'sender' => $this->when(
                $this->relationLoaded('sender') && $this->sender,
                fn () => [
                    'id' => $this->sender->id,
                    'name' => $this->sender->name,
                ],
            ),
            'attachments' => SupportTicketAttachmentResource::collection(
                $this->whenLoaded('attachments')
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
