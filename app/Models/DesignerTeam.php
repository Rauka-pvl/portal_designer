<?php

namespace App\Models;

use App\Enums\TeamMemberStatus;
use App\Enums\TeamRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DesignerTeam extends Model
{
    protected $table = 'designer_teams';

    protected $fillable = [
        'owner_id',
        'name',
        'status',
        'max_members',
    ];

    protected $casts = [
        'max_members' => 'integer',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(DesignerTeamMember::class, 'team_id');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(DesignerTeamInvitation::class, 'team_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'team_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function activeMembersCount(): int
    {
        return $this->members()
            ->where('status', TeamMemberStatus::Active->value)
            ->count();
    }

    public function pendingInvitationsCount(): int
    {
        return $this->invitations()
            ->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->count();
    }

    /** Seats used = active members + pending invitations. */
    public function usedSeats(): int
    {
        return $this->activeMembersCount() + $this->pendingInvitationsCount();
    }

    public function seatsRemaining(): int
    {
        return max(0, (int) $this->max_members - $this->usedSeats());
    }

    public function hasSeatAvailable(): bool
    {
        return $this->seatsRemaining() > 0;
    }

    public function memberFor(User|int $user): ?DesignerTeamMember
    {
        $userId = $user instanceof User ? (int) $user->id : (int) $user;

        return $this->members()->where('user_id', $userId)->first();
    }

    public function roleFor(User|int $user): ?TeamRole
    {
        $member = $this->memberFor($user);
        if (! $member || $member->status !== TeamMemberStatus::Active) {
            return null;
        }

        if ($member->role instanceof TeamRole) {
            return $member->role;
        }

        return TeamRole::tryFrom((string) $member->role);
    }
}
