<?php

namespace App\Http\Requests\Api;

use App\Enums\ProjectStageType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('stage_id') && ! $this->filled('stage_type')) {
            // Mobile may send stage_type value via stage_id alias when using enum keys.
            $this->merge(['stage_type' => $this->input('stage_id')]);
        }
        if ($this->has('items') && ! $this->has('steps')) {
            $this->merge(['steps' => $this->input('items')]);
        }
    }

    public function rules(): array
    {
        return [
            'stage_type' => ['required', 'string', Rule::in(ProjectStageType::values())],
            'stage_id' => ['nullable'],
            'name' => ['nullable', 'string', 'max:255'],
            'template_id' => ['nullable', 'integer'],
            'deadline' => ['nullable', 'date'],
            'responsible_id' => ['nullable', 'integer'],
            'assign_task' => ['nullable', 'boolean'],
            'save_as_template' => ['nullable', 'boolean'],
            'template_name' => ['nullable', 'string', 'max:255'],
            'items' => ['nullable', 'array'],
            'items.*.title' => ['required_with:items', 'string', 'max:1000'],
            'items.*.position' => ['nullable', 'integer', 'min:0'],
            'steps' => ['nullable', 'array'],
            'steps.*.title' => ['required_with:steps', 'string', 'max:1000'],
        ];
    }
}
