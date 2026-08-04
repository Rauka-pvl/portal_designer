<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'price',
        'currency',
        'billing_period',
        'included_seats',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'included_seats' => 'integer',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    public static function findByKey(string $key): ?self
    {
        return static::query()->where('key', $key)->where('status', 'active')->first();
    }

    public static function corporate(): ?self
    {
        return static::findByKey('corporate');
    }

    public static function personal(): ?self
    {
        return static::findByKey('personal');
    }
}
