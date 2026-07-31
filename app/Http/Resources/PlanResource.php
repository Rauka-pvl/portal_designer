<?php

namespace App\Http\Resources;

use App\Support\Api\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'key' => $this['key'],
            'name' => ucfirst((string) $this['key']),
            'price' => Money::formatMoney((int) $this['price']) ?? '0.00',
            'currency' => 'KZT',
            'billing_period' => 'month',
            'period_days' => (int) $this['period_days'],
            'included_seats' => (int) $this['max_members'],
            'max_members' => (int) $this['max_members'],
            'recommended' => (bool) $this['recommended'],
            'features' => $this['feature_keys'],
            'feature_keys' => $this['feature_keys'],
        ];
    }
}
