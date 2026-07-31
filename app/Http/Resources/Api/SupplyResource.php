<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'project_id' => (int) $this->project_id,
            'client_id' => $this->client_id ? (int) $this->client_id : ($this->project?->client_id ? (int) $this->project->client_id : null),
            'user_id' => (int) $this->user_id,
            'supplier_id' => (int) $this->supplier_id,
            'supplier_name' => $this->supplier?->name,
            'status' => $this->status,
            'is_sent_to_supplier' => (bool) $this->is_sent_to_supplier,
            'summa' => number_format((float) $this->summa, 2, '.', ''),
            'bonus_percent' => $this->bonus_percent === null ? null : number_format((float) $this->bonus_percent, 2, '.', ''),
            'category' => $this->category,
            'mark' => $this->mark,
            'room' => $this->room,
            'date_planned' => $this->date_planned?->toDateString(),
            'date_actual' => $this->date_actual?->toDateString(),
            'prepayment_date' => $this->prepayment_date?->toDateString(),
            'payment_date' => $this->payment_date?->toDateString(),
            'prepayment_amount' => $this->prepayment_amount === null ? null : number_format((float) $this->prepayment_amount, 2, '.', ''),
            'payment_amount' => $this->payment_amount === null ? null : number_format((float) $this->payment_amount, 2, '.', ''),
            'links' => array_values($this->links ?? []),
            'files' => array_values($this->files ?? []),
            'comment' => $this->comment,
            'items' => SupplyItemResource::collection(collect($this->product_items ?? [])),
            'checklist_item_ids' => array_values($this->included_step_ids ?? []),
            'offer' => $this->offerPayload('designer'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
