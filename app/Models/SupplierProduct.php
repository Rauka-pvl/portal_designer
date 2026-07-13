<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierProduct extends Model
{
    protected $table = 'supplier_products';

    protected $fillable = [
        'supplier_id',
        'name',
        'sku',
        'category',
        'description',
        'price',
        'unit',
        'image_path',
    ];

    protected $casts = [
        'supplier_id' => 'integer',
        'price' => 'decimal:2',
    ];

    protected $appends = ['image_url'];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset('storage/'.$this->image_path) : null;
    }

    /**
     * Ключ дедупликации: пара supplier_id + нормализованное имя (и артикул, если есть).
     */
    public static function dedupeKey(?string $name, ?string $sku = null): string
    {
        $sku = trim((string) $sku);
        if ($sku !== '') {
            return 'sku:'.mb_strtolower($sku);
        }

        return 'name:'.mb_strtolower(trim((string) $name));
    }
}
