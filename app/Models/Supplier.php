<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $table = 'suppliers';

    protected $fillable = [
        'user_id',
        'profile_status',
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
}
