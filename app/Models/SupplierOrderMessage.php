<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierOrderMessage extends Model
{
    protected $fillable = [
        'supplier_order_id',
        'sender_user_id',
        'message',
        'read_by_designer_at',
        'read_by_supplier_at',
    ];

    protected $casts = [
        'read_by_designer_at' => 'datetime',
        'read_by_supplier_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Supplier_orders::class, 'supplier_order_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }
}
