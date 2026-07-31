<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ReorderPipelineStagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('stages') && ! $this->has('stage_ids')) {
            $ids = collect($this->input('stages', []))
                ->sortBy('position')
                ->pluck('id')
                ->filter()
                ->values()
                ->all();
            $this->merge(['stage_ids' => $ids]);
        }
    }

    public function rules(): array
    {
        return [
            'stage_ids' => ['required', 'array', 'min:1'],
            'stage_ids.*' => ['integer'],
            'stages' => ['nullable', 'array'],
            'stages.*.id' => ['required_with:stages', 'integer'],
            'stages.*.position' => ['nullable', 'integer'],
        ];
    }
}
