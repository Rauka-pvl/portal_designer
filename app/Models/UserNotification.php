<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotification extends Model
{
    protected $table = 'user_notifications';

    protected $fillable = [
        'user_id',
        'title',
        'comment',
        'is_read',
        'read_at',
        'related_supplier_id',
        'action_key',
        'related_order_id',
        'related_post_id',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'related_supplier_id' => 'integer',
        'related_order_id' => 'integer',
        'related_post_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
