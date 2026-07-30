<?php

namespace App\Models;

use App\Enums\DesignerTaskStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DesignerTask extends Model
{
    use SoftDeletes;

    protected $table = 'designer_tasks';

    protected $fillable = [
        'creator_id',
        'assignee_id',
        'project_id',
        'team_id',
        'title',
        'description',
        'status',
        'due_at',
        'completed_at',
    ];

    protected $casts = [
        'status' => DesignerTaskStatus::class,
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(DesignerTeam::class, 'team_id');
    }

    public function isOverdue(): bool
    {
        if (! $this->due_at) {
            return false;
        }

        if ($this->status === DesignerTaskStatus::Completed || $this->status === DesignerTaskStatus::Cancelled) {
            return false;
        }

        return $this->due_at->isPast();
    }

    public function isDueToday(): bool
    {
        return $this->due_at && $this->due_at->isToday();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            DesignerTaskStatus::Completed->value,
            DesignerTaskStatus::Cancelled->value,
        ]);
    }

    public function applyStatus(DesignerTaskStatus $status): void
    {
        $this->status = $status;
        if ($status === DesignerTaskStatus::Completed) {
            $this->completed_at = $this->completed_at ?? now();
        } else {
            $this->completed_at = null;
        }
    }
}
