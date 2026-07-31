<?php

namespace App\Http\Resources;

use App\Support\WorkspaceAccess;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if (is_array($this->resource)) return $this->resource;
        $status = $this->status?->value ?? (string) $this->status;
        return [
            'id' => (int) $this->id,
            'source_type' => 'designer_task',
            'title' => $this->title,
            'description' => $this->description,
            'status' => $status,
            'due_at' => $this->due_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'project' => $this->project_id ? ['id' => (int) $this->project_id, 'name' => $this->project?->name] : null,
            'assignee' => ['id' => (int) $this->assignee_id, 'name' => $this->assignee?->name],
            'creator' => ['id' => (int) $this->creator_id, 'name' => $this->creator?->name],
            'is_overdue' => $this->isOverdue(),
            'permissions' => ['edit' => WorkspaceAccess::canFullyEditDesignerTask($request->user(), $this->resource), 'change_status' => WorkspaceAccess::canChangeDesignerTaskStatus($request->user(), $this->resource)],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
