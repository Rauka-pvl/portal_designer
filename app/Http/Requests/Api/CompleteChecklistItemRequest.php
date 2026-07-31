<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CompleteChecklistItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('completed') && ! $this->has('done')) {
            $this->merge(['done' => $this->boolean('completed')]);
        }
    }

    public function rules(): array
    {
        return [
            'completed' => ['nullable', 'boolean'],
            'done' => ['required', 'boolean'],
        ];
    }
}
