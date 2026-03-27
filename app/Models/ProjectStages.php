<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectStages extends Model
{
    protected $table = 'project_stages';

    protected $fillable = [
        'project_id',
        'stage_type',
        'template_id',
        'deadline',
        'responsible_id',
        'assign_task',
        'order',
    ];

    protected $casts = [
        'assign_task' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    public function responsible()
    {
        return $this->belongsTo(User::class);
    }

    public function steps()
    {
        return $this->hasMany(ProjectStageStep::class, 'project_stage_id');
    }
}
