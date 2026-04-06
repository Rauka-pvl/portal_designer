<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PassportObject extends Model
{
    use SoftDeletes;

    protected $table = 'passport_objects';

    protected $fillable = [
        'user_id',
        'client_id',
        'city',
        'address',
        'apartment',
        'apartment_floor',
        'apartment_entrance',
        'type',
        'status',
        'area',
        'repair_budget_planned',
        'repair_budget_actual',
        'repair_budget_per_m2_planned',
        'repair_budget_per_m2_actual',
        'links',
        'file_paths',
        'comment',
        'latitude',
        'longitude',
        'moderation_status',
        'moderation_duplicate_of_object_id',
        'moderation_comment',
        'moderation_reviewer_id',
        'moderation_reviewed_at',
    ];

    protected $casts = [
        'links' => 'array',
        'file_paths' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
        'moderation_reviewed_at' => 'datetime',
    ];

    /**
     * Другой дизайнер: квартира с теми же latitude, longitude (как в БД, без округления в запросе)
     * и теми же квартирой / подъездом / этажом.
     */
    public static function findOtherDesignerApartmentDuplicate(
        int $userId,
        float $latitude,
        float $longitude,
        ?string $apartment,
        ?string $entrance,
        ?string $floor,
        ?int $excludeObjectId = null
    ): ?self {
        $query = self::query()
            ->where('user_id', '!=', $userId)
            ->where('type', 'apartment')
            ->where('latitude', $latitude)
            ->where('longitude', $longitude)
            ->whereRaw('LOWER(TRIM(COALESCE(apartment, ""))) = ?', [mb_strtolower(trim((string) $apartment))])
            ->whereRaw('LOWER(TRIM(COALESCE(apartment_entrance, ""))) = ?', [mb_strtolower(trim((string) $entrance))])
            ->whereRaw('LOWER(TRIM(COALESCE(apartment_floor, ""))) = ?', [mb_strtolower(trim((string) $floor))]);

        if ($excludeObjectId !== null) {
            $query->where('id', '!=', $excludeObjectId);
        }

        return $query->first();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function moderationDuplicateOf()
    {
        return $this->belongsTo(self::class, 'moderation_duplicate_of_object_id');
    }
}
