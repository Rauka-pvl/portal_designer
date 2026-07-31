<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChecklistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $steps = $this->whenLoaded('steps');
        return [
            'id' => (int) $this->id,
            'project_id' => (int) $this->project_id,
            'stage_type' => $this->stage_type,
            'name' => $this->name,
            'template_id' => $this->template_id ? (int) $this->template_id : null,
            'deadline' => $this->deadline
                ? \Carbon\Carbon::parse($this->deadline)->toDateString()
                : null,
            'responsible_id' => $this->responsible_id ? (int) $this->responsible_id : null,
            'assign_task' => (bool) $this->assign_task,
            'order' => (int) $this->order,
            'items' => ChecklistItemResource::collection($steps),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
