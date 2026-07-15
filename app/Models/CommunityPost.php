<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommunityPost extends Model
{
    use SoftDeletes;

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_HIDDEN = 'hidden';

    public const VISIBILITY_PUBLIC = 'public';

    public const CATEGORIES = [
        'new_arrival',
        'work',
        'project',
        'idea',
        'useful',
        'looking_for',
        'other',
    ];

    protected $fillable = [
        'user_id',
        'text',
        'category',
        'city',
        'status',
        'visibility',
        'likes_count',
        'comments_count',
        'saves_count',
        'views_count',
    ];

    protected $casts = [
        'likes_count' => 'integer',
        'comments_count' => 'integer',
        'saves_count' => 'integer',
        'views_count' => 'integer',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(CommunityPostMedia::class)->orderBy('sort_order')->orderBy('id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(CommunityPostLike::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CommunityPostComment::class)->whereNull('parent_id');
    }

    public function allComments(): HasMany
    {
        return $this->hasMany(CommunityPostComment::class);
    }

    public function saves(): HasMany
    {
        return $this->hasMany(CommunityPostSave::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(CommunityPostReport::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->where('visibility', self::VISIBILITY_PUBLIC);
    }
}
