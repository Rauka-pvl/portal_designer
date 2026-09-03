<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $property = $this->propertySnapshot();
        $steps = $this->relationLoaded('stages') ? $this->stages->flatMap->steps : collect();
        $stage = $this->relationLoaded('pipelineStage') ? $this->pipelineStage : null;
        $files = is_array($this->files) ? array_values(array_filter($this->files, 'is_string')) : [];

        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'status' => $this->status,
            'stage_id' => $stage?->id,
            'client' => $this->client_id ? [
                'id' => (int) $this->client_id,
                'name' => $property['client_name'],
            ] : null,
            'responsible' => $this->user ? [
                'id' => (int) $this->user_id,
                'name' => $this->user->name,
            ] : null,
            'stage' => $stage ? new ProjectStageResource($stage) : null,
            'start_date' => $this->start_date ? Carbon::parse($this->start_date)->toIso8601String() : null,
            'planned_completion_date' => $this->planned_end_date ? Carbon::parse($this->planned_end_date)->toIso8601String() : null,
            'actual_completion_date' => $this->actual_end_date ? Carbon::parse($this->actual_end_date)->toIso8601String() : null,
            'planned_end_date' => $this->planned_end_date ? Carbon::parse($this->planned_end_date)->toIso8601String() : null,
            'actual_end_date' => $this->actual_end_date ? Carbon::parse($this->actual_end_date)->toIso8601String() : null,
            'planned_cost' => (string) ($this->planned_cost ?? 0),
            'actual_cost' => (string) ($this->actual_cost ?? 0),
            'renovation_budget_plan' => $property['repair_budget_planned'] === null
                ? null
                : (string) $property['repair_budget_planned'],
            'renovation_budget_fact' => $property['repair_budget_actual'] === null
                ? null
                : (string) $property['repair_budget_actual'],
            'city' => $property['city'],
            'object_type' => $property['type'],
            'object_address' => $property['address'],
            'apartment' => $property['apartment'],
            'apartment_floor' => $property['apartment_floor'],
            'apartment_entrance' => $property['apartment_entrance'],
            'area' => $property['area'] === null ? null : (string) $property['area'],
            'latitude' => $property['latitude'] === null ? null : (string) $property['latitude'],
            'longitude' => $property['longitude'] === null ? null : (string) $property['longitude'],
            'property' => [
                'client_id' => $property['client_id'],
                'client_name' => $property['client_name'],
                'city' => $property['city'],
                'object_type' => $property['type'],
                'object_address' => $property['address'],
                // Legacy aliases (same values)
                'type' => $property['type'],
                'address' => $property['address'],
                'apartment' => $property['apartment'],
                'apartment_floor' => $property['apartment_floor'],
                'apartment_entrance' => $property['apartment_entrance'],
                'area' => $property['area'] === null ? null : (string) $property['area'],
                'latitude' => $property['latitude'] === null ? null : (string) $property['latitude'],
                'longitude' => $property['longitude'] === null ? null : (string) $property['longitude'],
                'repair_budget_planned' => $property['repair_budget_planned'] === null
                    ? null
                    : (string) $property['repair_budget_planned'],
                'repair_budget_actual' => $property['repair_budget_actual'] === null
                    ? null
                    : (string) $property['repair_budget_actual'],
                'repair_budget_per_m2_planned' => $property['repair_budget_per_m2_planned'] === null
                    ? null
                    : (string) $property['repair_budget_per_m2_planned'],
                'repair_budget_per_m2_actual' => $property['repair_budget_per_m2_actual'] === null
                    ? null
                    : (string) $property['repair_budget_per_m2_actual'],
            ],
            'checklist_progress' => [
                'total' => $steps->count(),
                'done' => $steps->where('result_status', 'done')->count(),
                'percent' => $steps->count()
                    ? (int) round($steps->where('result_status', 'done')->count() / $steps->count() * 100)
                    : 0,
            ],
            'counts' => [
                'checklist_stages' => $this->relationLoaded('stages') ? $this->stages->count() : 0,
                'tasks' => $this->tasks_count ?? 0,
            ],
            'comment' => $this->comment,
            'links' => $this->links ?? [],
            'files' => array_map(
                fn (string $path) => Storage::disk('public')->url($path),
                $files
            ),
            'file_items' => array_map(fn (string $path) => [
                'path' => $path,
                'name' => basename($path),
                'url' => Storage::disk('public')->url($path),
            ], $files),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
