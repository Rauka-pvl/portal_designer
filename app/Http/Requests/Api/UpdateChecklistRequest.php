<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'stage_type' => ['sometimes', 'string', 'max:100'],
            'name' => ['nullable', 'string', 'max:255'],
            'template_id' => ['nullable', 'integer'],
            'deadline' => ['nullable', 'date'],
            'responsible_id' => ['nullable', 'integer'],
            'assign_task' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
            'items' => ['nullable', 'array'],
            'items.*.title' => ['required_with:items', 'string', 'max:1000'],
        ];
    }
}
