<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Template extends Model
{
    use SoftDeletes;

    protected $table = 'templates';

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'steps',
    ];

    protected $casts = [
        'steps' => 'array',
    ];
}
