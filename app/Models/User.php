<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /** @var list<string> */
    private const LEGACY_SUBSCRIPTION_KEYS = [
        'subscription_plan',
        'subscription_ends_at',
        'subscription_trial_ends_at',
        'subscription_trial_used',
        'subscription_cancelled_at',
        'subscription_cancel_reason',
        'subscription_payment_method',
        'role',
    ];

    /** @var array<string, mixed> */
    private array $pendingSubscriptionSync = [];

    protected $fillable = [
        'name',
        'email',
        'password',
        'must_change_password',
        'password_changed_at',
        'phone',
        'account_type',
        'account_status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'password_changed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            foreach (self::LEGACY_SUBSCRIPTION_KEYS as $key) {
                unset($user->attributes[$key]);
            }
        });

        static::saved(function (User $user): void {
            if ($user->pendingSubscriptionSync === []) {
                return;
            }

            $user->syncPendingSubscription();
        });
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(UserNotification::class)->latest();
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function supplierProfile(): HasOne
    {
        return $this->hasOne(Supplier::class, 'user_id');
    }

    public function designerProfile(): HasOne
    {
        return $this->hasOne(DesignerProfile::class);
    }

    public function cashbackTransactions(): HasMany
    {
        return $this->hasMany(DesignerCashbackTransaction::class)->latest();
    }

    public function subscriptionPayments(): HasMany
    {
        return $this->hasMany(DesignerSubscriptionPayment::class)->latest();
    }

    public function communityPosts(): HasMany
    {
        return $this->hasMany(CommunityPost::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class)->latest();
    }

    public function scopeWithDesignerProfile(Builder $query): Builder
    {
        return $query->with('designerProfile');
    }

    public function isDesigner(): bool
    {
        return $this->account_type === 'designer';
    }

    public function isSupplier(): bool
    {
        return $this->account_type === 'supplier';
    }

    public function isSystemAdmin(): bool
    {
        return $this->account_type === 'system_admin';
    }

    public function getRoleAttribute(): string
    {
        return (string) ($this->attributes['account_type'] ?? 'designer');
    }

    public function setRoleAttribute(mixed $value): void
    {
        if (in_array($value, ['designer', 'supplier', 'system_admin', 'admin'], true)) {
            $this->attributes['account_type'] = $value === 'admin' ? 'system_admin' : $value;
        }
    }

    public function getSubscriptionPlanAttribute(): ?string
    {
        if (array_key_exists('subscription_plan', $this->pendingSubscriptionSync)) {
            $pending = $this->pendingSubscriptionSync['subscription_plan'];

            return $pending === null ? null : (string) $pending;
        }

        $subscription = $this->subscription;
        if (! $subscription || in_array($subscription->status, ['cancelled', 'expired'], true)) {
            // Cancelled with no future access should look like "no plan".
            if ($subscription && $subscription->status === 'cancelled'
                && ! $subscription->expires_at && ! $subscription->trial_ends_at) {
                return null;
            }
            if ($subscription && $subscription->status === 'cancelled') {
                return null;
            }
        }

        return $subscription?->plan?->key;
    }

    public function getSubscriptionEndsAtAttribute(): ?Carbon
    {
        if (array_key_exists('subscription_ends_at', $this->pendingSubscriptionSync)) {
            $value = $this->pendingSubscriptionSync['subscription_ends_at'];

            return $value ? Carbon::parse($value) : null;
        }

        return $this->subscription?->expires_at;
    }

    public function getSubscriptionTrialEndsAtAttribute(): ?Carbon
    {
        if (array_key_exists('subscription_trial_ends_at', $this->pendingSubscriptionSync)) {
            $value = $this->pendingSubscriptionSync['subscription_trial_ends_at'];

            return $value ? Carbon::parse($value) : null;
        }

        return $this->subscription?->trial_ends_at;
    }

    public function getSubscriptionTrialUsedAttribute(): bool
    {
        if (array_key_exists('subscription_trial_used', $this->pendingSubscriptionSync)) {
            return (bool) $this->pendingSubscriptionSync['subscription_trial_used'];
        }

        return $this->subscriptions()
            ->whereNotNull('trial_ends_at')
            ->exists();
    }

    public function getSubscriptionCancelledAtAttribute(): ?Carbon
    {
        if (array_key_exists('subscription_cancelled_at', $this->pendingSubscriptionSync)) {
            $value = $this->pendingSubscriptionSync['subscription_cancelled_at'];

            return $value ? Carbon::parse($value) : null;
        }

        return $this->subscription?->cancelled_at;
    }

    public function getSubscriptionCancelReasonAttribute(): ?string
    {
        if (array_key_exists('subscription_cancel_reason', $this->pendingSubscriptionSync)) {
            return $this->pendingSubscriptionSync['subscription_cancel_reason'];
        }

        return $this->subscription?->cancel_reason;
    }

    public function getSubscriptionPaymentMethodAttribute(): ?string
    {
        if (array_key_exists('subscription_payment_method', $this->pendingSubscriptionSync)) {
            return $this->pendingSubscriptionSync['subscription_payment_method'];
        }

        $payment = $this->subscriptionPayments()->latest('id')->first();
        $meta = $payment?->meta;

        return is_array($meta) ? ($meta['payment_method'] ?? null) : null;
    }

    public function setSubscriptionPlanAttribute(mixed $value): void
    {
        $this->pendingSubscriptionSync['subscription_plan'] = $value;
    }

    public function setSubscriptionEndsAtAttribute(mixed $value): void
    {
        $this->pendingSubscriptionSync['subscription_ends_at'] = $value;
    }

    public function setSubscriptionTrialEndsAtAttribute(mixed $value): void
    {
        $this->pendingSubscriptionSync['subscription_trial_ends_at'] = $value;
    }

    public function setSubscriptionTrialUsedAttribute(mixed $value): void
    {
        $this->pendingSubscriptionSync['subscription_trial_used'] = (bool) $value;
    }

    public function setSubscriptionCancelledAtAttribute(mixed $value): void
    {
        $this->pendingSubscriptionSync['subscription_cancelled_at'] = $value;
    }

    public function setSubscriptionCancelReasonAttribute(mixed $value): void
    {
        $this->pendingSubscriptionSync['subscription_cancel_reason'] = $value;
    }

    public function setSubscriptionPaymentMethodAttribute(mixed $value): void
    {
        $this->pendingSubscriptionSync['subscription_payment_method'] = $value;
    }

    private function syncPendingSubscription(): void
    {
        $data = $this->pendingSubscriptionSync;
        $this->pendingSubscriptionSync = [];

        $subscription = $this->subscriptions()->latest('id')->first();

        // Explicit null plan clears inherited/personal subscription marker.
        if (array_key_exists('subscription_plan', $data) && $data['subscription_plan'] === null) {
            if ($subscription) {
                $subscription->status = 'cancelled';
                $subscription->expires_at = null;
                $subscription->trial_ends_at = null;
                $subscription->cancelled_at = now();
                $subscription->save();
            }
            $this->unsetRelation('subscription');
            $this->unsetRelation('subscriptions');

            return;
        }

        // Persist "trial already used" even when only the legacy flag is set.
        if (array_key_exists('subscription_trial_used', $data)
            && $data['subscription_trial_used']
            && ! array_key_exists('subscription_trial_ends_at', $data)
            && ! $subscription?->trial_ends_at) {
            $data['subscription_trial_ends_at'] = now()->subSecond();
        }

        $planKey = $data['subscription_plan']
            ?? $subscription?->plan?->key
            ?? (array_key_exists('subscription_trial_ends_at', $data) ? 'pro' : null);

        if ($planKey === null || $planKey === '') {
            if (! array_key_exists('subscription_ends_at', $data)
                && ! array_key_exists('subscription_trial_ends_at', $data)
                && ! array_key_exists('subscription_cancelled_at', $data)
                && ! array_key_exists('subscription_trial_used', $data)) {
                return;
            }
            $planKey = $subscription?->plan?->key ?? 'personal';
        }

        $defaults = [
            'personal' => ['name' => 'Personal', 'price' => 0, 'included_seats' => 1],
            'standard' => ['name' => 'Standard', 'price' => 5000, 'included_seats' => 1],
            'pro' => ['name' => 'Pro', 'price' => 9990, 'included_seats' => 1],
            'corporate' => ['name' => 'Corporate', 'price' => 29990, 'included_seats' => 5],
        ];
        $planMeta = $defaults[$planKey] ?? ['name' => ucfirst((string) $planKey), 'price' => 0, 'included_seats' => 1];
        $plan = SubscriptionPlan::query()->updateOrCreate(
            ['key' => $planKey],
            [
                'name' => $planMeta['name'],
                'price' => $planMeta['price'],
                'currency' => 'KZT',
                'billing_period' => 'month',
                'included_seats' => $planMeta['included_seats'],
                'status' => 'active',
            ]
        );

        $subscription ??= new Subscription(['user_id' => $this->id]);
        $subscription->plan_id = $plan->id;

        if (array_key_exists('subscription_ends_at', $data)) {
            $subscription->expires_at = $data['subscription_ends_at'];
        }
        if (array_key_exists('subscription_trial_ends_at', $data)) {
            $subscription->trial_ends_at = $data['subscription_trial_ends_at'];
        }
        if (array_key_exists('subscription_cancelled_at', $data)) {
            $subscription->cancelled_at = $data['subscription_cancelled_at'];
        }
        if (array_key_exists('subscription_cancel_reason', $data)) {
            $subscription->cancel_reason = $data['subscription_cancel_reason'];
        }

        $trialEnds = $subscription->trial_ends_at;
        $expires = $subscription->expires_at;
        if ($subscription->cancelled_at) {
            $subscription->status = 'cancelled';
        } elseif ($trialEnds && $trialEnds->isFuture() && (! $expires || $expires->isPast())) {
            $subscription->status = 'trial';
        } elseif ($expires && $expires->isFuture()) {
            $subscription->status = 'active';
        } elseif ($expires || $trialEnds) {
            $subscription->status = 'expired';
        } else {
            $subscription->status = 'active';
        }

        if (! $subscription->starts_at) {
            $subscription->starts_at = now();
        }

        $subscription->save();
        $this->unsetRelation('subscription');
        $this->unsetRelation('subscriptions');
    }
}
