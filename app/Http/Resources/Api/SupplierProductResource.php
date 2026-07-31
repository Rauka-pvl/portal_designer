<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => (int) $this->id, 'supplier_id' => (int) $this->supplier_id, 'name' => $this->name, 'sku' => $this->sku, 'category' => $this->category, 'description' => $this->description, 'price' => $this->price === null ? null : number_format((float) $this->price, 2, '.', ''), 'unit' => $this->unit, 'image_url' => $this->image_url, 'created_at' => $this->created_at?->toIso8601String(), 'updated_at' => $this->updated_at?->toIso8601String()];
    }
}
