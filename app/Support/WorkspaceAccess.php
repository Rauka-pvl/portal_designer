<?php

namespace App\Support;

use App\Enums\TeamRole;
use App\Models\Project;
use App\Models\User;
use App\Services\Team\TeamService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Project / task visibility for personal vs Corporate workspaces.
 */
class WorkspaceAccess
{
    public static function teams(): TeamService
    {
        return app(TeamService::class);
    }

    public static function activeTeamId(User $user): ?int
    {
        return self::teams()->activeTeamFor($user)?->id;
    }

    public static function role(User $user): ?TeamRole
    {
        return self::teams()->roleInActiveTeam($user);
    }

    public static function isCorporate(User $user): bool
    {
        return self::teams()->isCorporateUser($user);
    }

    /**
     * Scope projects visible to the user.
     *
     * @param  Builder<Project>  $query
     * @return Builder<Project>
     */
    public static function scopeProjects(Builder $query, User $user): Builder
    {
        $team = self::teams()->activeTeamFor($user);
        if ($team && self::teams()->teamHasCorporateAccess($team)) {
            return $query->where(function (Builder $q) use ($user, $team) {
                $q->where('team_id', $team->id)
                    ->orWhere(function (Builder $personal) use ($user) {
                        $personal->where('user_id', $user->id)->whereNull('team_id');
                    });
            });
        }

        return $query->where('user_id', $user->id);
    }

    public static function canAccessProject(User $user, Project $project): bool
    {
        $team = self::teams()->activeTeamFor($user);
        if ($team && self::teams()->teamHasCorporateAccess($team)) {
            if ((int) $project->team_id === (int) $team->id) {
                return true;
            }
        }

        return (int) $project->user_id === (int) $user->id;
    }

    public static function canSeeAllTeamTasks(User $user): bool
    {
        $role = self::role($user);

        return $role?->seesAllTeamTasks() ?? false;
    }

    /**
     * Apply checklist-stage visibility for calendar/tasks.
     * Designer role: own created stages OR responsible assignments.
     *
     * @param  Builder<\App\Models\ProjectStageStep>  $query
     * @return Builder<\App\Models\ProjectStageStep>
     */
    public static function scopeChecklistSteps(Builder $query, User $user): Builder
    {
        $team = self::teams()->activeTeamFor($user);
        $userId = (int) $user->id;

        if ($team && self::teams()->teamHasCorporateAccess($team)) {
            // Same visibility as projects: team projects OR personal projects still owned by the user.
            $query->whereHas('stage.project', function (Builder $q) use ($team, $userId) {
                $q->where(function (Builder $inner) use ($team, $userId) {
                    $inner->where('team_id', $team->id)
                        ->orWhere(function (Builder $personal) use ($userId) {
                            $personal->where('user_id', $userId)->whereNull('team_id');
                        });
                });
            });

            if (! self::canSeeAllTeamTasks($user)) {
                $query->where(function (Builder $q) use ($userId) {
                    $q->where('responsible_id', $userId)
                        ->orWhereHas('stage', function (Builder $stage) use ($userId) {
                            $stage->where('responsible_id', $userId)
                                ->orWhere('created_by', $userId);
                        });
                });
            }

            return $query;
        }

        return $query->whereHas('stage.project', function (Builder $q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    /**
     * @param  Builder<\App\Models\DesignerTask>  $query
     * @return Builder<\App\Models\DesignerTask>
     */
    public static function scopeDesignerTasks(Builder $query, User $user): Builder
    {
        $userId = (int) $user->id;
        $team = self::teams()->activeTeamFor($user);

        if ($team && self::teams()->teamHasCorporateAccess($team)) {
            if (self::canSeeAllTeamTasks($user)) {
                return $query->where(function (Builder $q) use ($team, $userId) {
                    $q->where('team_id', $team->id)
                        ->orWhere(function (Builder $personal) use ($userId) {
                            $personal->whereNull('team_id')
                                ->where(function (Builder $own) use ($userId) {
                                    $own->where('creator_id', $userId)
                                        ->orWhere('assignee_id', $userId);
                                });
                        });
                });
            }

            return $query->where(function (Builder $q) use ($userId, $team) {
                $q->where(function (Builder $teamTasks) use ($userId, $team) {
                    $teamTasks->where('team_id', $team->id)
                        ->where(function (Builder $own) use ($userId) {
                            $own->where('creator_id', $userId)
                                ->orWhere('assignee_id', $userId);
                        });
                })->orWhere(function (Builder $personal) use ($userId) {
                    $personal->whereNull('team_id')
                        ->where(function (Builder $own) use ($userId) {
                            $own->where('creator_id', $userId)
                                ->orWhere('assignee_id', $userId);
                        });
                });
            });
        }

        return $query->where(function (Builder $q) use ($userId) {
            $q->where('creator_id', $userId)->orWhere('assignee_id', $userId);
        });
    }

    public static function canAccessDesignerTask(User $user, \App\Models\DesignerTask $task): bool
    {
        return self::scopeDesignerTasks(
            \App\Models\DesignerTask::query()->whereKey($task->id),
            $user
        )->exists();
    }

    public static function canFullyEditDesignerTask(User $user, \App\Models\DesignerTask $task): bool
    {
        if (! self::canAccessDesignerTask($user, $task)) {
            return false;
        }

        if (self::isCorporate($user) && self::canSeeAllTeamTasks($user)) {
            return true;
        }

        return (int) $task->creator_id === (int) $user->id;
    }

    public static function canChangeDesignerTaskStatus(User $user, \App\Models\DesignerTask $task): bool
    {
        if (! self::canAccessDesignerTask($user, $task)) {
            return false;
        }

        if (self::canFullyEditDesignerTask($user, $task)) {
            return true;
        }

        return (int) $task->assignee_id === (int) $user->id;
    }

    public static function attachTeamOnCreate(User $user, Project $project): void
    {
        $team = self::teams()->activeTeamFor($user);
        if ($team && self::teams()->teamHasCorporateAccess($team)) {
            $project->team_id = $team->id;
        }
    }
}
