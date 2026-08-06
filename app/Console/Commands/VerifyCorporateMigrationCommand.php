<?php

namespace App\Console\Commands;

use App\Enums\TeamMemberStatus;
use App\Models\DesignerTeam;
use App\Models\DesignerTeamMember;
use App\Models\Project;
use App\Models\User;
use Illuminate\Console\Command;

class VerifyCorporateMigrationCommand extends Command
{
    protected $signature = 'corporate:verify-migration';

    protected $description = 'Verify Corporate teams, seats, and project links';

    public function handle(): int
    {
        $corporateKeys = \App\Models\SubscriptionPlan::query()
            ->where('type', \App\Models\SubscriptionPlan::TYPE_CORPORATE)
            ->pluck('key');

        $corporateOwners = User::query()
            ->whereHas('subscription.plan', fn ($q) => $q->whereIn('key', $corporateKeys))
            ->get(['id', 'name', 'email']);

        $this->info('Corporate subscriptions: '.$corporateOwners->count());
        foreach ($corporateOwners as $owner) {
            $this->line("  #{$owner->id} {$owner->email}");
        }

        $teams = DesignerTeam::query()->withCount([
            'members',
            'projects',
            'invitations',
        ])->get();

        $this->info('Teams: '.$teams->count());
        $errors = 0;

        foreach ($teams as $team) {
            $used = $team->usedSeats();
            $maxLabel = $team->max_members === null ? '∞' : (string) $team->max_members;
            $this->line(sprintf(
                '  Team #%d "%s" status=%s seats=%d/%s members=%d projects=%d invites=%d',
                $team->id,
                $team->name,
                $team->status,
                $used,
                $maxLabel,
                $team->members_count,
                $team->projects_count,
                $team->invitations_count,
            ));

            if ($team->max_members !== null && $used > (int) $team->max_members) {
                $this->error("    ERROR: seat limit exceeded ({$used}/{$team->max_members})");
                $errors++;
            }
        }

        $multiTeamUsers = DesignerTeamMember::query()
            ->select('user_id')
            ->where('status', TeamMemberStatus::Active->value)
            ->whereHas('team', fn ($q) => $q->where('status', 'active'))
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('user_id');

        if ($multiTeamUsers->isNotEmpty()) {
            $this->error('Users in multiple active teams: '.$multiTeamUsers->implode(', '));
            $errors += $multiTeamUsers->count();
        } else {
            $this->info('No users in multiple active teams.');
        }

        $orphanTeamProjects = Project::query()
            ->whereNotNull('team_id')
            ->whereDoesntHave('team')
            ->count();

        if ($orphanTeamProjects > 0) {
            $this->error("Orphan projects with invalid team_id: {$orphanTeamProjects}");
            $errors += $orphanTeamProjects;
        } else {
            $this->info('No orphan team projects.');
        }

        $ownersWithoutTeam = $corporateOwners->filter(function (User $owner) {
            return ! DesignerTeam::query()
                ->where('owner_id', $owner->id)
                ->where('status', 'active')
                ->exists();
        });

        if ($ownersWithoutTeam->isNotEmpty()) {
            $this->warn('Corporate owners without active team: '.$ownersWithoutTeam->pluck('id')->implode(', '));
        }

        $this->info($errors === 0 ? 'Verification OK.' : "Verification finished with {$errors} error(s).");

        return $errors === 0 ? self::SUCCESS : self::FAILURE;
    }
}
