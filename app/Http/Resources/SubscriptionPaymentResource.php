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
        $method = (string) ($meta['payment_method'] ?? '');
        $isTrial = (bool) ($meta['is_trial'] ?? false) || $this->status === 'trial';
        $listPrice = isset($meta['list_price']) ? (int) $meta['list_price'] : null;
        $statusKey = match ((string) $this->status) {
            'trial' => 'trial',
            'pending' => 'pending',
            'failed' => 'failed',
            'refunded' => 'refunded',
            'cancelled' => 'cancelled',
            default => 'paid',
        };

        return [
            'id' => $this->id,
            'plan' => $this->plan,
            'amount' => DesignerSubscription::formatMoney((int) $this->amount),
            'amount_raw' => (int) $this->amount,
            'list_price' => $listPrice === null ? null : DesignerSubscription::formatMoney($listPrice),
            'list_price_raw' => $listPrice,
            'period_days' => (int) $this->period_days,
            'starts_at' => $this->starts_at?->toISOString(),
            'ends_at' => $this->ends_at?->toISOString(),
            'status' => $this->status,
            'status_key' => $statusKey,
            'is_trial' => $isTrial,
            'has_receipt' => ! $isTrial && (int) $this->amount > 0 && $statusKey === 'paid',
            'payment_method' => $method !== '' ? $method : null,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
