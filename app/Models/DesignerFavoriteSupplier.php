<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesignerFavoriteSupplier extends Model
{
    protected $fillable = [
        'designer_user_id',
        'supplier_id',
    ];

    public function designer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'designer_user_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
