<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectStageStep extends Model
{
    protected $table = 'project_stages_steps';

    protected $fillable = [
        'project_stage_id',
        'title',
        'deadline',
        'responsible_id',
        'link',
        'result_status',
        'result_comment',
        'order',
    ];

    protected $casts = [
        'result_status' => 'string',
    ];

    public function stage()
    {
        return $this->belongsTo(ProjectStages::class, 'project_stage_id');
    }
}

