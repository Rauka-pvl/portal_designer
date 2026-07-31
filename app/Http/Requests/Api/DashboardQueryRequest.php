<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class DashboardQueryRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'from' => $this->input('from', $this->input('date_from')),
            'to' => $this->input('to', $this->input('date_to')),
        ]);
    }

    public function rules(): array
    {
        return [
            'period' => ['nullable', 'in:week,month,quarter,year,custom'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'timezone' => ['nullable', 'timezone'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('period', 'month') === 'custom') {
                foreach (['from' => 'date_from', 'to' => 'date_to'] as $field => $alias) {
                    if (! $this->filled($field) && ! $this->filled($alias)) {
                        $validator->errors()->add($alias, 'The '.$alias.' field is required for a custom period.');
                    }
                }
            }
        });
    }
}
