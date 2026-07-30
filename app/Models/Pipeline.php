<?php

namespace App\Models;

use App\Enums\PipelineType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pipeline extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'name',
        'is_default',
    ];

    protected $casts = [
        'type' => PipelineType::class,
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stages(): HasMany
    {
        return $this->hasMany(PipelineStage::class)->orderBy('position');
    }

    public function activeStages(): HasMany
    {
        return $this->stages()->where('is_active', true);
    }

    public static function defaultForUser(int $userId, PipelineType $type): self
    {
        return static::query()
            ->where('user_id', $userId)
            ->where('type', $type)
            ->where('is_default', true)
            ->with('stages')
            ->firstOrFail();
    }
}
