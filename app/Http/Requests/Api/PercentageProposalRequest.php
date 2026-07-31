<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class PercentageProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('percentage') && ! $this->has('bonus_percent')) {
            $this->merge(['bonus_percent' => $this->input('percentage')]);
        }
    }

    public function rules(): array
    {
        return [
            'bonus_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'message' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
