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
        'team_id',
        'client_id',
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
        'city',
        'address',
        'apartment',
        'apartment_floor',
        'apartment_entrance',
        'object_type',
        'area',
        'latitude',
        'longitude',

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
        'area' => 'float',
        'latitude' => 'float',
        'longitude' => 'float',
        'moderation_reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function team()
    {
        return $this->belongsTo(DesignerTeam::class, 'team_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function object()
    {
        return $this->belongsTo(PassportObject::class);
    }

    public function stages()
    {
        return $this->hasMany(ProjectStages::class, 'project_id');
    }

    public function objectDetails()
    {
        return $this->hasOne(ProjectObjectDetail::class);
    }

    public function supplierOrders()
    {
        return $this->hasMany(Supplier_orders::class, 'project_id');
    }

    public function activityEvents()
    {
        return $this->hasMany(ActivityEvent::class, 'subject_id')
            ->where('subject_type', 'project')
            ->orderByDesc('id');
    }

    public function checklistProgress(): array
    {
        $steps = $this->stages->flatMap->steps;
        $total = $steps->count();
        $done = $steps->where('result_status', 'done')->count();

        return [
            'total' => $total,
            'done' => $done,
            'percent' => $total > 0 ? (int) round(($done / $total) * 100) : 0,
        ];
    }

    /**
     * Resolve property/client display data: project columns first, then object_details, then legacy passport.
     */
    public function propertySnapshot(): array
    {
        $details = $this->objectDetails;
        $legacy = $this->object;

        $area = $this->area ?? $details?->area ?? $legacy?->area;
        $budgetPlan = $details?->repair_budget_planned ?? null;
        $budgetFact = $details?->repair_budget_actual ?? null;

        // Fall back to project planned/actual cost for older records without details budgets
        if ($budgetPlan === null) {
            $budgetPlan = $this->planned_cost;
        }
        if ($budgetFact === null) {
            $budgetFact = $this->actual_cost;
        }

        $areaFloat = is_numeric($area) ? (float) $area : 0.0;
        $planFloat = is_numeric($budgetPlan) ? (float) $budgetPlan : null;
        $factFloat = is_numeric($budgetFact) ? (float) $budgetFact : null;

        $lat = $this->latitude ?? $details?->latitude ?? $legacy?->latitude;
        $lng = $this->longitude ?? $details?->longitude ?? $legacy?->longitude;

        return [
            'client_id' => $this->client_id ?? $details?->client_id ?? $legacy?->client_id,
            'client_name' => $this->client?->full_name
                ?? $details?->client?->full_name
                ?? $legacy?->client?->full_name,
            'city' => $this->city ?: ($details?->city ?? $legacy?->city),
            'address' => $this->address ?: ($details?->address ?? $legacy?->address),
            'apartment' => $this->apartment ?: ($details?->apartment ?? $legacy?->apartment),
            'apartment_floor' => $this->apartment_floor ?: ($details?->apartment_floor ?? $legacy?->apartment_floor),
            'apartment_entrance' => $this->apartment_entrance ?: ($details?->apartment_entrance ?? $legacy?->apartment_entrance),
            'type' => $this->object_type ?: ($details?->type ?? $legacy?->type),
            'area' => $areaFloat > 0 ? $areaFloat : ($area !== null ? (float) $area : null),
            'latitude' => is_numeric($lat) ? (float) $lat : null,
            'longitude' => is_numeric($lng) ? (float) $lng : null,
            'repair_budget_planned' => $planFloat,
            'repair_budget_actual' => $factFloat,
            'repair_budget_per_m2_planned' => ($areaFloat > 0 && $planFloat !== null)
                ? round($planFloat / $areaFloat, 2)
                : null,
            'repair_budget_per_m2_actual' => ($areaFloat > 0 && $factFloat !== null)
                ? round($factFloat / $areaFloat, 2)
                : null,
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Project $project) {
            $project->stages()->get()->each->delete();
        });
    }
}
