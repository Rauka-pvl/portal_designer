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
        'summa',
        'category',
        'mark',
        'room',
        'date_planned',
        'date_actual',
        'prepayment_date',
        'payment_date',
        'prepayment_amount',
        'payment_amount',
        'links',
        'files',
        'comment',
    ];

    protected $casts = [
        'links' => 'array',
        'files' => 'array',
        'date_planned' => 'date',
        'date_actual' => 'date',
        'prepayment_date' => 'date',
        'payment_date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
