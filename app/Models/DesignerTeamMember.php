<?php

namespace App\Models;

use App\Enums\TeamMemberStatus;
use App\Enums\TeamRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesignerTeamMember extends Model
{
    protected $table = 'designer_team_members';

    protected $fillable = [
        'team_id',
        'user_id',
        'role',
        'status',
        'invited_by',
        'joined_at',
    ];

    protected $casts = [
        'role' => TeamRole::class,
        'status' => TeamMemberStatus::class,
        'joined_at' => 'datetime',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(DesignerTeam::class, 'team_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isActive(): bool
    {
        return $this->status === TeamMemberStatus::Active;
    }
}
