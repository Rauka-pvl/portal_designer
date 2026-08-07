<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\SupportTicket */
class SupportTicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->statusEnum();
        $category = $this->category;

        return [
            'id' => $this->id,
            'number' => $this->number,
            'subject' => $this->subject,
            'category' => $category?->value ?? (string) $this->category,
            'category_label' => $category?->label(),
            'status' => $status->value,
            'status_label' => $status->label(),
            'is_open' => $status->isOpen(),
            'is_priority' => (bool) $this->is_priority,
            'plan_code' => $this->plan_code_snapshot,
            'can_reply' => $request->user()?->can('reply', $this->resource) ?? false,
            'author' => $this->when(
                $this->relationLoaded('author') && $this->author,
                fn () => [
                    'id' => $this->author->id,
                    'name' => $this->author->name,
                    'email' => $this->author->email,
                ],
            ),
            'team' => $this->when(
                $this->relationLoaded('team') && $this->team,
                fn () => [
                    'id' => $this->team->id,
                    'name' => $this->team->name,
                ],
            ),
            'plan' => $this->when(
                $this->relationLoaded('plan') && $this->plan,
                fn () => [
                    'id' => $this->plan->id,
                    'key' => $this->plan->key,
                    'name' => $this->plan->name,
                ],
            ),
            'messages' => SupportTicketMessageResource::collection(
                $this->whenLoaded('messages')
            ),
            'messages_count' => $this->when(
                isset($this->messages_count),
                (int) $this->messages_count,
            ),
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
