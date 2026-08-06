<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory;

    public const TYPE_INDIVIDUAL = 'individual';

    public const TYPE_CORPORATE = 'corporate';

    protected $fillable = [
        'key',
        'type',
        'name',
        'description',
        'price',
        'currency',
        'billing_period',
        'included_seats',
        'max_users',
        'max_projects',
        'priority_support',
        'annual_discount_percent',
        'annual_price',
        'feature_keys',
        'recommended',
        'status',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'annual_price' => 'decimal:2',
        'included_seats' => 'integer',
        'max_users' => 'integer',
        'max_projects' => 'integer',
        'priority_support' => 'boolean',
        'annual_discount_percent' => 'integer',
        'feature_keys' => 'array',
        'recommended' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')->where('is_active', true);
    }

    public static function findByKey(string $key): ?self
    {
        return static::query()->where('key', $key)->active()->first();
    }

    public static function findByKeyIncludingArchived(string $key): ?self
    {
        return static::query()->where('key', $key)->first();
    }

    public function isCorporate(): bool
    {
        return $this->type === self::TYPE_CORPORATE;
    }

    public function isIndividual(): bool
    {
        return $this->type === self::TYPE_INDIVIDUAL;
    }

    /** null limit = unlimited */
    public function unlimitedUsers(): bool
    {
        return $this->max_users === null;
    }

    /** null limit = unlimited */
    public function unlimitedProjects(): bool
    {
        return $this->max_projects === null;
    }

    public function monthlyPriceInt(): int
    {
        return (int) round((float) $this->price);
    }

    public function annualPriceInt(): ?int
    {
        return $this->annual_price === null ? null : (int) round((float) $this->annual_price);
    }

    /** Money saved vs paying monthly for 12 months. */
    public function yearlySavings(): int
    {
        $annual = $this->annualPriceInt();
        if ($annual === null) {
            return 0;
        }

        return max(0, $this->monthlyPriceInt() * 12 - $annual);
    }

    /** Equivalent monthly cost when billed annually (rounded). */
    public function annualMonthlyEquivalent(): ?int
    {
        $annual = $this->annualPriceInt();
        if ($annual === null) {
            return null;
        }

        return (int) round($annual / 12);
    }

    /** Back-compat: used by legacy code paths that need a Corporate plan for seat sync. */
    public static function corporate(): ?self
    {
        return static::query()->active()->where('type', self::TYPE_CORPORATE)->orderBy('sort_order')->first();
    }

    public static function personal(): ?self
    {
        return static::findByKeyIncludingArchived('personal');
    }
}
