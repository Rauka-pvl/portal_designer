<?php

namespace App\Models;

use App\Enums\TeamRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DesignerTeamInvitation extends Model
{
    protected $table = 'designer_team_invitations';

    protected $fillable = [
        'team_id',
        'email',
        'role',
        'token',
        'status',
        'invited_by',
        'expires_at',
        'accepted_at',
    ];

    protected $casts = [
        'role' => TeamRole::class,
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(DesignerTeam::class, 'team_id');
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public static function makeToken(): string
    {
        return Str::random(64);
    }

    public function isPending(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        return ! $this->expires_at || $this->expires_at->isFuture();
    }
}
