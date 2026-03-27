<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PassportObject extends Model
{
    use SoftDeletes;

    protected $table = 'passport_objects';

    protected $fillable = [
        'user_id',
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
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
