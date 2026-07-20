<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierGuaranteePayment extends Model
{
    public const STATUS_CREATED = 'created';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_REFUNDED = 'refunded';

    public const TYPE_GUARANTEE_DEPOSIT = 'supplier_guarantee_deposit';

    protected $fillable = [
        'user_id',
        'supplier_id',
        'uuid',
        'type',
        'amount',
        'currency',
        'status',
        'provider',
        'provider_payment_id',
        'idempotency_key',
        'payment_url',
        'expires_at',
        'paid_at',
        'provider_event_id',
        'provider_payload',
        'meta',
    ];

    protected $casts = [
        'amount' => 'integer',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'provider_payload' => 'array',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(SupplierGuaranteeLedgerEntry::class, 'payment_id');
    }

    public function isReusable(): bool
    {
        if (! in_array($this->status, [self::STATUS_CREATED, self::STATUS_PENDING], true)) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function isFinal(): bool
    {
        return in_array($this->status, [
            self::STATUS_PAID,
            self::STATUS_FAILED,
            self::STATUS_CANCELLED,
            self::STATUS_EXPIRED,
            self::STATUS_REFUNDED,
        ], true);
    }
}
