<?php

namespace App\Http\Requests\Api;

use App\Support\DesignerSubscription;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubscriptionCheckoutRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        return [
            'plan' => ['required', Rule::in(array_keys(DesignerSubscription::plans()))],
            'payment_method' => ['required', Rule::in([
                DesignerSubscription::METHOD_KASPI,
                DesignerSubscription::METHOD_CARD,
                DesignerSubscription::METHOD_PROMO,
            ])],
            'promo_code' => ['nullable', 'string', 'max:100'],
            'card_number' => ['nullable', 'string', 'max:32'],
            'card_expiry' => ['nullable', 'string', 'max:10'],
        ];
    }
}
