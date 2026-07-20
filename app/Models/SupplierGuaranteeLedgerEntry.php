<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierGuaranteeLedgerEntry extends Model
{
    protected $table = 'supplier_guarantee_ledger';

    public const TYPE_INITIAL_TOP_UP = 'initial_top_up';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'supplier_id',
        'user_id',
        'payment_id',
        'type',
        'amount',
        'currency',
        'balance_after',
        'source',
        'status',
        'idempotency_key',
        'description',
        'meta',
    ];

    protected $casts = [
        'amount' => 'integer',
        'balance_after' => 'integer',
        'meta' => 'array',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(SupplierGuaranteePayment::class, 'payment_id');
    }
}
