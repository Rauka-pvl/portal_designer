<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesignerProfile extends Model
{
    protected $fillable = [
        'user_id',
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

    protected $casts = [
        'price_per_m2' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
