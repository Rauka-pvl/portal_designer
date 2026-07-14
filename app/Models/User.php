<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'role',
        'name',
        'email',
        'password',
        'must_change_password',
        'password_changed_at',
        'subscription_trial_ends_at',
        'subscription_plan',
        'subscription_ends_at',
        'subscription_trial_used',
        'subscription_payment_method',
        'subscription_cancelled_at',
        'phone',
        'city',
        'short_description',
        'work_regions',
        'about_designer',
        'website_portfolio',
        'telegram',
        'whatsapp',
        'vk',
        'instagram',
        'experience',
        'price_per_m2',
        'education',
        'awards',
        'specialization',
        'styles',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'password_changed_at' => 'datetime',
            'subscription_trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'subscription_trial_used' => 'boolean',
            'subscription_cancelled_at' => 'datetime',
            'price_per_m2' => 'decimal:2',
        ];
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(UserNotification::class)->latest();
    }

    /**
     * Карточка компании, привязанная к аккаунту поставщика (user_id).
     */
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

    public function scopeWithDesignerProfile(Builder $query): Builder
    {
        return $query->with('designerProfile');
    }
}
