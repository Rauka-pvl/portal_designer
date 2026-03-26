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
        'is_favorite',
    ];

    protected $casts = [
        'recommend' => 'boolean',
        'address_match' => 'boolean',
        'is_favorite' => 'boolean',
        'brands' => 'array',
        'cities_presence' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
