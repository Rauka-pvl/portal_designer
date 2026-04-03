<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;

    protected $table = 'projects';

    protected $fillable = [
        'user_id',
        'object_id',
        'name',
        'status',
        'start_date',
        'planned_end_date',
        'actual_end_date',
        'actual_cost',
        'planned_cost',
        'links',
        'files',
        'comment',

        // Moderation
        'moderation_status',
        'moderation_reason',
        'moderation_comment',
        'moderation_reviewer_id',
        'moderation_reviewed_at',
    ];

    protected $casts = [
        'links' => 'array',
        'files' => 'array',
        'moderation_reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function object()
    {
        return $this->belongsTo(PassportObject::class);
    }

    public function stages()
    {
        return $this->hasMany(ProjectStages::class, 'project_id');
    }

    protected static function booted(): void
    {
        static::deleting(function (Project $project) {
            // Project uses SoftDeletes, so DB-level cascade is not triggered.
            // Manually remove child stages to avoid orphaned stage/step rows.
            $project->stages()->get()->each->delete();
        });
    }
}
