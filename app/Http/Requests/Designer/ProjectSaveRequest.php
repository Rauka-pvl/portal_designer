<?php

namespace App\Http\Requests\Designer;

use App\Enums\PipelineType;
use App\Enums\ProjectStageType;
use App\Models\Pipeline;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectSaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('status')) {
            return;
        }

        $userId = (int) $this->user()?->id;
        if (! $userId) {
            return;
        }

        $first = Pipeline::defaultForUser($userId, PipelineType::Project)
            ?->stages()
            ->orderBy('position')
            ->value('system_key');

        if ($first) {
            $this->merge(['status' => $first]);
        }
    }

    public function rules(): array
    {
        $userId = (int) $this->user()->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:64'],
            'client_id' => [
                'nullable',
                'integer',
                Rule::exists('clients', 'id')->where(fn ($q) => $q->where('user_id', $userId)),
            ],
            'start_date' => ['nullable', 'date'],
            'planned_end_date' => ['nullable', 'date'],
            'actual_end_date' => ['nullable', 'date'],
            'comment' => ['nullable', 'string'],

            // Legacy property fields (optional; no longer required by UI)
            'city' => ['nullable', 'string', 'max:255'],
            'object_type' => ['nullable', Rule::in(['apartment', 'house', 'commercial', 'office', 'other'])],
            'object_address' => ['nullable', 'string', 'max:500'],
            'apartment_floor' => ['nullable', 'string', 'max:50'],
            'apartment_entrance' => ['nullable', 'string', 'max:50'],
            'apartment' => ['nullable', 'string', 'max:50'],
            'area' => ['nullable', 'numeric', 'min:0'],
            'repair_budget_planned' => ['nullable', 'numeric', 'min:0'],
            'repair_budget_actual' => ['nullable', 'numeric', 'min:0'],

            // Links as [{title, url}]
            'links' => ['nullable', 'array'],
            'links.*.title' => ['nullable', 'string', 'max:255'],
            'links.*.url' => ['required_with:links.*.title', 'nullable', 'url', 'max:1000'],

            'existing_files' => ['nullable', 'array'],
            'existing_files.*' => ['nullable', 'string', 'max:1000'],
            'files' => ['nullable', 'array'],
            'files.*' => ['nullable', 'file', 'max:10240'],

            'stages' => ['nullable', 'array'],
            'stages.*.id' => ['nullable', 'integer'],
            'stages.*.stage_type' => ['required_with:stages', Rule::in(ProjectStageType::values())],
            'stages.*.name' => ['nullable', 'string', 'max:255'],
            'stages.*.template_id' => ['nullable', 'integer'],
            'stages.*.deadline' => ['nullable', 'date'],
            'stages.*.assign_task' => ['nullable', 'boolean'],
            'stages.*.responsible_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('id', $userId)),
            ],
            'stages.*.steps' => ['nullable', 'array'],
            'stages.*.steps.*.id' => ['nullable', 'integer'],
            'stages.*.steps.*.title' => ['nullable', 'string', 'max:1000'],
            'stages.*.steps.*.deadline' => ['nullable', 'date'],
            'stages.*.steps.*.responsible_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('id', $userId)),
            ],
            'stages.*.steps.*.link' => ['nullable', 'url', 'max:1000'],
            'stages.*.steps.*.result_status' => ['nullable', 'string', Rule::in(['pending', 'done'])],
            'stages.*.steps.*.result_comment' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
