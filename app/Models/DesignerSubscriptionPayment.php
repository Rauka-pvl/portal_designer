<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesignerSubscriptionPayment extends Model
{
    protected $fillable = [
        'user_id',
        'plan',
        'amount',
        'period_days',
        'starts_at',
        'ends_at',
        'status',
        'meta',
    ];

    protected $casts = [
        'amount' => 'integer',
        'period_days' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
