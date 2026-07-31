<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChecklistItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $completed = (string) ($this->result_status ?? 'pending') === 'done';

        return [
            'id' => (int) $this->id,
            'checklist_id' => (int) $this->project_stage_id,
            'title' => (string) $this->title,
            'position' => (int) ($this->order ?? 0),
            'order' => (int) ($this->order ?? 0),
            'deadline' => $this->deadline
                ? (\Carbon\Carbon::parse($this->deadline)->toIso8601String())
                : null,
            'responsible_id' => $this->responsible_id ? (int) $this->responsible_id : null,
            'link' => $this->link,
            'completed' => $completed,
            'completed_at' => $completed ? $this->updated_at?->toIso8601String() : null,
            'result' => $this->result_comment,
            'result_comment' => $this->result_comment,
            'result_status' => $this->result_status ?: 'pending',
            'result_updated_at' => filled($this->result_comment) ? $this->updated_at?->toIso8601String() : null,
            'has_result' => filled($this->result_comment),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
