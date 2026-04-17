<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class Supplier extends Model
{
    use SoftDeletes;

    protected $table = 'suppliers';

    protected $fillable = [
        'user_id',
        'created_by_user_id',
        'profile_status',
        'temporary_password_encrypted',
        'logo',
        'name',
        'recommend',
        'phone',
        'email',
        'telegram',
        'whatsapp',
        'website',
        'city',
        'address',
        'sphere',
        'work_terms_type',
        'work_terms_value',
        'brands',
        'cities_presence',
        'comment',
        'org_form',
        'inn',
        'kpp',
        'ogrn',
        'okpo',
        'legal_address',
        'actual_address',
        'address_match',
        'director',
        'accountant',
        'bik',
        'bank',
        'checking_account',
        'corr_account',
        'comment_bank',

        // Moderation
        'moderation_status',
        'moderation_comment',
        'moderation_reviewer_id',
        'moderation_reviewed_at',
        'is_confirmed_by_designer',
        'is_referral_submitted',
    ];

    protected $casts = [
        'recommend' => 'boolean',
        'address_match' => 'boolean',
        'is_confirmed_by_designer' => 'boolean',
        'is_referral_submitted' => 'boolean',
        'moderation_reviewed_at' => 'datetime',
        'brands' => 'array',
        'cities_presence' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function accountUser()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function setTemporaryPassword(string $password): void
    {
        $this->temporary_password_encrypted = Crypt::encryptString($password);
    }

    public function getTemporaryPasswordForModeratorAttribute(): ?string
    {
        $encrypted = $this->temporary_password_encrypted;
        if (! is_string($encrypted) || trim($encrypted) === '') {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (\Throwable) {
            return null;
        }
    }
}
