<?php

namespace App\Http\Requests\Api;

use App\Support\DesignerSubscription;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubscriptionPaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'payment_method' => [
                'required',
                Rule::in([
                    DesignerSubscription::METHOD_KASPI,
                    DesignerSubscription::METHOD_CARD,
                ]),
            ],
        ];
    }
}
