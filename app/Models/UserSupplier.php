<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSupplier extends Model
{
    protected $table = 'user_suppliers';

    protected $fillable = [
        'designer_user_id',
        'supplier_id',
        'status',
        'invited_at',
        'accepted_at',
        'rejected_at',
    ];

    protected $casts = [
        'invited_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function designer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'designer_user_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
