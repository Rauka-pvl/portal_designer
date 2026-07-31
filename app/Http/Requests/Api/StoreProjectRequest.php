<?php

namespace App\Http\Requests\Api;

use App\Enums\PipelineType;
use App\Http\Requests\Designer\ProjectSaveRequest;
use App\Models\PipelineStage;

class StoreProjectRequest extends ProjectSaveRequest
{
    protected function prepareForValidation(): void
    {
        $stage = $this->filled('stage_id')
            ? PipelineStage::query()->whereKey($this->input('stage_id'))
                ->whereHas('pipeline', fn ($q) => $q->where('user_id', $this->user()?->id)->where('type', PipelineType::Project))
                ->first()
            : null;
        $this->merge(array_filter([
            'planned_end_date' => $this->input('planned_end_date', $this->input('planned_completion_date')),
            'actual_end_date' => $this->input('actual_end_date', $this->input('actual_completion_date')),
            'repair_budget_planned' => $this->input('repair_budget_planned', $this->input('renovation_budget_plan')),
            'repair_budget_actual' => $this->input('repair_budget_actual', $this->input('renovation_budget_fact')),
            'status' => $stage?->system_key,
        ], fn ($v) => $v !== null));
        parent::prepareForValidation();
    }

    public function rules(): array
    {
        return parent::rules() + ['stage_id' => ['nullable', 'integer', 'exists:pipeline_stages,id']];
    }
}
