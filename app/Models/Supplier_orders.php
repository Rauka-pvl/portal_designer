<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier_orders extends Model
{
    use SoftDeletes;

    protected $table = 'supplier_orders';

    protected $fillable = [
        'user_id',
        'project_id',
        'supplier_id',
        'status',
    ];
}
