<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'brands' => ['nullable', 'array'],
            'cities_presence' => ['nullable', 'array'],
            'logo' => ['nullable', 'image', 'max:1024'],
            'inn' => ['nullable', 'string', 'max:255'],
        ];
    }
}
