<?php

namespace App\Services\Team;

use App\Models\Project;
use App\Models\ProjectStages;
use App\Models\DesignerTask;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Route;

class AssignmentNotifier
{
    public function notifyChecklistAssigned(
        User $actor,
        ?int $previousResponsibleId,
        ?int $newResponsibleId,
        Project $project,
        ProjectStages $stage,
    ): void {
        if (! $newResponsibleId || (int) $newResponsibleId === (int) $actor->id) {
            return;
        }

        if ($previousResponsibleId !== null && (int) $previousResponsibleId === (int) $newResponsibleId) {
            return;
        }

        $deadline = $stage->deadline
            ? $stage->deadline->format('d.m.Y')
            : '—';

        $checklistName = $stage->name ?: (string) $stage->stage_type;
        $projectName = (string) $project->name;

        $comment = __('team.notify_checklist_assigned_body', [
            'checklist' => $checklistName,
            'project' => $projectName,
            'deadline' => $deadline,
            'actor' => $actor->name,
        ]);

        $url = null;
        if (Route::has('tasks.index')) {
            $url = route('tasks.index', [
                'project' => $project->id,
                'checklist' => $stage->id,
            ]);
        }

        UserNotification::query()->create([
            'user_id' => $newResponsibleId,
            'title' => __('team.notify_checklist_assigned_title'),
            'comment' => $url ? ($comment.' '.$url) : $comment,
            'action_key' => 'checklist_assigned',
            'is_read' => false,
        ]);
    }

    public function notifyTaskAssigned(
        User $actor,
        ?int $previousAssigneeId,
        ?int $newAssigneeId,
        DesignerTask $task,
    ): void {
        if (! $newAssigneeId || (int) $newAssigneeId === (int) $actor->id) {
            return;
        }

        if ($previousAssigneeId !== null && (int) $previousAssigneeId === (int) $newAssigneeId) {
            return;
        }

        $deadline = $task->due_at
            ? $task->due_at->timezone(config('app.timezone'))->format('d.m.Y H:i')
            : '—';

        $projectName = $task->project?->name;
        $comment = $projectName
            ? __('tasks.notify_assigned_body_with_project', [
                'title' => $task->title,
                'project' => $projectName,
                'deadline' => $deadline,
                'actor' => $actor->name,
            ])
            : __('tasks.notify_assigned_body', [
                'title' => $task->title,
                'deadline' => $deadline,
                'actor' => $actor->name,
            ]);

        $url = route('tasks.index', ['task' => $task->id, 'view' => 'kanban']);

        UserNotification::query()->create([
            'user_id' => $newAssigneeId,
            'title' => __('tasks.notify_assigned_title'),
            'comment' => $comment.' '.$url,
            'action_key' => 'task_assigned',
            'is_read' => false,
        ]);
    }
}
