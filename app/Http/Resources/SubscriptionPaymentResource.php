<?php

namespace App\Http\Resources;

use App\Support\DesignerSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $meta = is_array($this->meta) ? $this->meta : [];

        return [
            'id' => $this->id,
            'plan' => $this->plan,
            'amount' => DesignerSubscription::formatMoney((int) $this->amount),
            'period_days' => (int) $this->period_days,
            'starts_at' => $this->starts_at?->toISOString(),
            'ends_at' => $this->ends_at?->toISOString(),
            'status' => $this->status,
            'payment_method' => $meta['payment_method'] ?? null,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
