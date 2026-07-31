<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplyItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer'],
            'quantity' => ['nullable', 'numeric', 'min:0.01'],
            'qty' => ['nullable', 'numeric', 'min:0.01'],
            'price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
