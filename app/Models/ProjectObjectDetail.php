<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectObjectDetail extends Model
{
    protected $fillable = [
        'project_id',
        'passport_object_id',
        'client_id',
        'city',
        'address',
        'apartment',
        'apartment_floor',
        'apartment_entrance',
        'type',
        'status',
        'area',
        'repair_budget_planned',
        'repair_budget_actual',
        'repair_budget_per_m2_planned',
        'repair_budget_per_m2_actual',
        'links',
        'file_paths',
        'comment',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'links' => 'array',
        'file_paths' => 'array',
        'area' => 'float',
        'repair_budget_planned' => 'float',
        'repair_budget_actual' => 'float',
        'repair_budget_per_m2_planned' => 'float',
        'repair_budget_per_m2_actual' => 'float',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function passportObject(): BelongsTo
    {
        return $this->belongsTo(PassportObject::class, 'passport_object_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public static function syncFromPassport(Project $project, PassportObject $object): self
    {
        return static::query()->updateOrCreate(
            ['project_id' => $project->id],
            [
                'passport_object_id' => $object->id,
                'client_id' => $object->client_id,
                'city' => $object->city,
                'address' => $object->address,
                'apartment' => $object->apartment,
                'apartment_floor' => $object->apartment_floor,
                'apartment_entrance' => $object->apartment_entrance,
                'type' => $object->type,
                'status' => $object->status,
                'area' => $object->area,
                'repair_budget_planned' => $object->repair_budget_planned,
                'repair_budget_actual' => $object->repair_budget_actual,
                'repair_budget_per_m2_planned' => $object->repair_budget_per_m2_planned,
                'repair_budget_per_m2_actual' => $object->repair_budget_per_m2_actual,
                'links' => $object->links,
                'file_paths' => $object->file_paths,
                'comment' => $object->comment,
                'latitude' => $object->latitude,
                'longitude' => $object->longitude,
            ]
        );
    }
}
