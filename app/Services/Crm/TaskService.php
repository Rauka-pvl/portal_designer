<?php

namespace App\Services\Crm;

use App\Enums\DesignerTaskStatus;
use App\Models\DesignerTask;
use App\Models\ProjectStages;
use App\Models\ProjectStageStep;
use App\Models\User;
use App\Services\Team\AssignmentNotifier;
use App\Services\Team\TeamService;
use App\Support\WorkspaceAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TaskService
{
    public function findAccessible(User $user, int $id): DesignerTask
    {
        return WorkspaceAccess::scopeDesignerTasks(DesignerTask::query(), $user)->findOrFail($id);
    }

    public function save(User $user, array $data, ?DesignerTask $task = null): DesignerTask
    {
        $new = ! $task;
        $task ??= new DesignerTask(['creator_id' => $user->id, 'team_id' => WorkspaceAccess::activeTeamId($user)]);
        $previous = $task->assignee_id;
        $assignee = app(TeamService::class)->assertAssigneeAllowed($user, (int) ($data['assignee_id'] ?? $user->id));
        $task->fill([
            'title' => trim((string) $data['title']),
            'description' => isset($data['description']) ? trim((string) $data['description']) : null,
            'assignee_id' => $assignee,
            'project_id' => $data['project_id'] ?? null,
            'due_at' => $data['due_at'],
        ]);
        if (isset($data['status'])) $task->applyStatus(DesignerTaskStatus::from($data['status']));
        elseif ($new) $task->applyStatus(DesignerTaskStatus::New);
        $task->save();
        app(AssignmentNotifier::class)->notifyTaskAssigned($user, $previous, $assignee, $task->fresh(['project']));
        return $task;
    }

    public function updateStatus(DesignerTask $task, string $status): DesignerTask
    {
        $task->applyStatus(DesignerTaskStatus::from($status));
        $task->save();
        return $task;
    }

    public function kanbanCards(User $user, Request $request)
    {
        $tasks = WorkspaceAccess::scopeDesignerTasks(DesignerTask::query(), $user)
            ->with(['creator:id,name,email', 'assignee:id,name,email', 'project:id,name'])
            ->when($request->query('status'), fn ($q, $status) => $q->whereIn('status', array_intersect(is_array($status) ? $status : explode(',', $status), DesignerTaskStatus::values())))
            ->when($request->filled('project_id'), fn ($q) => $request->query('project_id') === 'none' ? $q->whereNull('project_id') : $q->where('project_id', $request->integer('project_id')))
            ->when($request->filled('assignee_id'), fn ($q) => $q->where('assignee_id', $request->integer('assignee_id')))
            ->orderBy('due_at')->get();
        return $tasks->concat($this->checklistKanbanCards($user, $request))->values();
    }

    public function checklistKanbanCards(User $user, Request $request)
    {
        $query = ProjectStages::query()->whereHas('project', fn ($q) => WorkspaceAccess::scopeProjects($q, $user))
            ->with(['project:id,name,user_id,team_id', 'responsible:id,name', 'steps:id,project_stage_id,result_status,deadline,responsible_id']);
        if (WorkspaceAccess::isCorporate($user) && ! WorkspaceAccess::canSeeAllTeamTasks($user)) {
            $query->where(fn ($q) => $q->where('responsible_id', $user->id)->orWhere('created_by', $user->id)->orWhereHas('steps', fn ($s) => $s->where('responsible_id', $user->id)));
        }
        if ($request->filled('project_id')) {
            if ($request->query('project_id') === 'none') return collect();
            $query->where('project_id', $request->integer('project_id'));
        }
        return $query->orderBy('deadline')->get()->map(function (ProjectStages $stage) {
            $total = $stage->steps->count(); $done = $stage->steps->where('result_status', 'done')->count();
            $deadline = $stage->deadline ? Carbon::parse($stage->deadline)->startOfDay() : null;
            $overdue = $deadline && $done < $total && $deadline->copy()->endOfDay()->isPast();
            $status = $total > 0 && $done === $total ? 'completed' : (($done || $overdue) ? 'in_progress' : 'new');
            return ['id' => 'checklist-'.$stage->id, 'checklist_id' => (int) $stage->id, 'source_type' => 'checklist',
                'title' => trim((string) $stage->name) ?: (string) $stage->stage_type, 'description' => null, 'status' => $status,
                'due_at' => $deadline?->toIso8601String(), 'completed_at' => null, 'assignee_id' => $stage->responsible_id,
                'assignee_name' => $stage->responsible?->name, 'project_id' => $stage->project_id, 'project_name' => $stage->project?->name,
                'is_overdue' => (bool) $overdue, 'read_only' => true];
        });
    }

    public function calendarEvents(User $user, string $start, string $end): array
    {
        $events = [];
        foreach (WorkspaceAccess::scopeDesignerTasks(DesignerTask::query()->whereBetween('due_at', [Carbon::parse($start)->startOfDay(), Carbon::parse($end)->endOfDay()]), $user)->with(['project:id,name', 'assignee:id,name'])->get() as $task) {
            $events[] = ['id' => 'designer_task:'.$task->id, 'source_type' => 'designer_task', 'source_id' => (int) $task->id,
                'date' => $task->due_at?->toDateString(), 'time' => $task->due_at?->format('H:i'), 'title' => $task->title,
                'status' => $task->status?->value ?? (string) $task->status, 'done' => ($task->status?->value ?? $task->status) === 'completed',
                'project_id' => $task->project_id, 'project_name' => $task->project?->name, 'assignee_name' => $task->assignee?->name];
        }
        foreach (WorkspaceAccess::scopeChecklistSteps(ProjectStageStep::query()->whereNotNull('deadline')->whereBetween('deadline', [$start, $end]), $user)->with('stage.project:id,name')->get() as $step) {
            if (! $step->stage?->project) continue;
            $events[] = ['id' => 'checklist_step:'.$step->id, 'source_type' => 'checklist_step', 'source_id' => (int) $step->id,
                'date' => (string) $step->deadline, 'time' => '10:00', 'title' => $step->title, 'status' => $step->result_status === 'done' ? 'done' : 'planned',
                'done' => $step->result_status === 'done', 'project_id' => $step->stage->project_id, 'project_name' => $step->stage->project->name,
                'project_stage_id' => $step->stage->id];
        }
        return $events;
    }
}
