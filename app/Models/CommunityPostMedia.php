<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityPostMedia extends Model
{
    protected $fillable = [
        'community_post_id',
        'file_path',
        'file_type',
        'width',
        'height',
        'sort_order',
    ];

    protected $casts = [
        'width' => 'integer',
        'height' => 'integer',
        'sort_order' => 'integer',
    ];

    protected $appends = ['url'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(CommunityPost::class, 'community_post_id');
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/'.ltrim((string) $this->file_path, '/'));
    }
}
