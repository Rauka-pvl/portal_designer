<?php

namespace App\Http\Resources;

use App\Support\Api\Money;
use App\Support\DesignerSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $featureKeys = $this['feature_keys'] ?? [];
        $comparison = [];
        foreach (DesignerSubscription::comparisonFeatureKeys() as $key) {
            $comparison[$key] = in_array($key, $featureKeys, true);
        }

        return [
            'key' => $this['key'],
            'name' => ucfirst((string) $this['key']),
            'price' => Money::formatMoney((int) $this['price']) ?? '0.00',
            'price_raw' => (int) $this['price'],
            'currency' => 'KZT',
            'billing_period' => 'month',
            'period_days' => (int) $this['period_days'],
            'included_seats' => (int) $this['max_members'],
            'max_members' => (int) $this['max_members'],
            'recommended' => (bool) $this['recommended'],
            'features' => $featureKeys,
            'feature_keys' => $featureKeys,
            'comparison_features' => $comparison,
            'limit_key' => $this['limit_key'] ?? null,
            'desc_key' => $this['desc_key'] ?? null,
        ];
    }
}
