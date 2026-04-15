<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
}
