<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplyItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'product_id' => (int) ($this['product_id'] ?? 0),
            'name' => (string) ($this['name'] ?? ''),
            'qty' => (int) ($this['qty'] ?? 1),
            'price' => isset($this['price']) ? number_format((float) $this['price'], 2, '.', '') : null,
            'unit' => $this['unit'] ?? null,
        ];
    }
}
