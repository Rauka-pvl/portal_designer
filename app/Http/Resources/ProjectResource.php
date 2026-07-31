<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $property = $this->propertySnapshot();
        $steps = $this->relationLoaded('stages') ? $this->stages->flatMap->steps : collect();
        $stage = $this->relationLoaded('pipelineStage') ? $this->pipelineStage : null;
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'status' => $this->status,
            'stage_id' => $stage?->id,
            'client' => $this->client_id ? ['id' => (int) $this->client_id, 'name' => $property['client_name']] : null,
            'responsible' => $this->user ? ['id' => (int) $this->user_id, 'name' => $this->user->name] : null,
            'stage' => $stage ? new ProjectStageResource($stage) : null,
            'start_date' => $this->start_date ? \Carbon\Carbon::parse($this->start_date)->toIso8601String() : null,
            'planned_completion_date' => $this->planned_end_date ? \Carbon\Carbon::parse($this->planned_end_date)->toIso8601String() : null,
            'actual_completion_date' => $this->actual_end_date ? \Carbon\Carbon::parse($this->actual_end_date)->toIso8601String() : null,
            'planned_end_date' => $this->planned_end_date ? \Carbon\Carbon::parse($this->planned_end_date)->toIso8601String() : null,
            'actual_end_date' => $this->actual_end_date ? \Carbon\Carbon::parse($this->actual_end_date)->toIso8601String() : null,
            'planned_cost' => (string) ($this->planned_cost ?? 0),
            'actual_cost' => (string) ($this->actual_cost ?? 0),
            'renovation_budget_plan' => $property['repair_budget_planned'] === null ? null : (string) $property['repair_budget_planned'],
            'renovation_budget_fact' => $property['repair_budget_actual'] === null ? null : (string) $property['repair_budget_actual'],
            'property' => collect($property)->map(fn ($v) => is_numeric($v) ? (string) $v : $v)->all(),
            'checklist_progress' => ['total' => $steps->count(), 'done' => $steps->where('result_status', 'done')->count(), 'percent' => $steps->count() ? (int) round($steps->where('result_status', 'done')->count() / $steps->count() * 100) : 0],
            'counts' => ['checklist_stages' => $this->relationLoaded('stages') ? $this->stages->count() : 0, 'tasks' => $this->tasks_count ?? 0],
            'comment' => $this->comment,
            'links' => $this->links ?? [],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
