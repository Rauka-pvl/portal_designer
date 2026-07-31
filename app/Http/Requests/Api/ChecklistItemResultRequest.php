<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ChecklistItemResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('result') && ! $this->has('result_comment')) {
            $this->merge(['result_comment' => $this->input('result')]);
        }
    }

    public function rules(): array
    {
        return [
            'result' => ['nullable', 'string'],
            'result_comment' => ['nullable', 'string'],
        ];
    }
}
