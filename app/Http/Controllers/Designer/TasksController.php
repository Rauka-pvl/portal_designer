<?php

namespace App\Http\Controllers\Designer;

use App\Enums\DesignerTaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Designer\DesignerTaskSaveRequest;
use App\Models\DesignerTask;
use App\Models\Project;
use App\Models\ProjectStages;
use App\Services\Team\AssignmentNotifier;
use App\Services\Team\TeamService;
use App\Support\WorkspaceAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class TasksController extends Controller
{
    private const STAGE_TYPES = ['measurement', 'planning', 'drawings', 'equipment', 'estimate', 'visualization'];

    public function __construct(
        private readonly TeamService $teams,
        private readonly AssignmentNotifier $notifier,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $assignees = $this->teams->assigneeOptions($user);

        $projects = WorkspaceAccess::scopeProjects(Project::query(), $user)
            ->orderBy('name')
            ->get(['id', 'name']);

        $activeCount = WorkspaceAccess::scopeDesignerTasks(DesignerTask::query(), $user)
            ->active()
            ->count();

        return view('designer.tasks.index', [
            'stageTypes' => self::STAGE_TYPES,
            'templatesData' => [],
            'users' => collect($assignees)->map(fn (array $o) => (object) [
                'id' => $o['id'],
                'name' => $o['name'],
                'email' => $o['email'],
            ]),
            'assigneeOptions' => $assignees,
            'isCorporate' => WorkspaceAccess::isCorporate($user),
            'projectsData' => $projects->map(fn (Project $p) => [
                'id' => (int) $p->id,
                'name' => (string) $p->name,
            ])->values(),
            'statusColumns' => DesignerTaskStatus::columns(),
            'activeTasksCount' => $activeCount,
            'currentUser' => [
                'id' => (int) $user->id,
                'name' => (string) $user->name,
                'email' => (string) $user->email,
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $user = $request->user();
        $q = WorkspaceAccess::scopeDesignerTasks(DesignerTask::query(), $user)
            ->with([
                'creator:id,name,email',
                'assignee:id,name,email',
                'project:id,name',
            ])
            ->orderBy('due_at');

        if ($search = trim((string) $request->query('q', ''))) {
            $like = '%'.$search.'%';
            $q->where(function ($w) use ($like) {
                $w->where('title', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhereHas('project', fn ($p) => $p->where('name', 'like', $like))
                    ->orWhereHas('assignee', fn ($a) => $a->where('name', 'like', $like));
            });
        }

        if ($status = $request->query('status')) {
            $statuses = is_array($status) ? $status : explode(',', (string) $status);
            $statuses = array_values(array_intersect($statuses, DesignerTaskStatus::values()));
            if ($statuses !== []) {
                $q->whereIn('status', $statuses);
            }
        }

        if ($request->filled('assignee_id')) {
            $q->where('assignee_id', (int) $request->query('assignee_id'));
        }

        if ($request->filled('creator_id')) {
            $q->where('creator_id', (int) $request->query('creator_id'));
        }

        if ($request->filled('project_id')) {
            if ($request->query('project_id') === 'none') {
                $q->whereNull('project_id');
            } else {
                $q->where('project_id', (int) $request->query('project_id'));
            }
        }

        if ($request->boolean('overdue')) {
            $q->whereNotIn('status', [DesignerTaskStatus::Completed->value, DesignerTaskStatus::Cancelled->value])
                ->where('due_at', '<', now());
        }

        if ($request->query('due') === 'today') {
            $q->whereDate('due_at', now()->toDateString());
        }

        $tasks = $q->get()->map(fn (DesignerTask $t) => $this->payload($t, $user))->values();

        $checklistCards = $this->checklistKanbanCards($user, $request);

        return response()->json([
            'success' => true,
            'tasks' => $tasks->concat($checklistCards)->values(),
            'active_count' => WorkspaceAccess::scopeDesignerTasks(DesignerTask::query(), $user)->active()->count()
                + $checklistCards->whereNotIn('status', ['completed', 'cancelled'])->count(),
            'columns' => DesignerTaskStatus::columns(),
        ]);
    }

    /**
     * Checklist stages as read-only kanban cards (status mapped from progress).
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function checklistKanbanCards($user, Request $request)
    {
        $stagesQuery = ProjectStages::query()
            ->whereHas('project', function ($q) use ($user) {
                WorkspaceAccess::scopeProjects($q, $user);
            })
            ->with([
                'project:id,name,user_id,team_id',
                'responsible:id,name',
                'steps:id,project_stage_id,result_status,deadline',
            ]);

        if (WorkspaceAccess::isCorporate($user) && ! WorkspaceAccess::canSeeAllTeamTasks($user)) {
            $userId = (int) $user->id;
            $stagesQuery->where(function ($q) use ($userId) {
                $q->where('responsible_id', $userId)
                    ->orWhere('created_by', $userId)
                    ->orWhereHas('steps', fn ($s) => $s->where('responsible_id', $userId));
            });
        }

        if ($search = trim((string) $request->query('q', ''))) {
            $like = '%'.$search.'%';
            $stagesQuery->where(function ($w) use ($like) {
                $w->where('name', 'like', $like)
                    ->orWhere('stage_type', 'like', $like)
                    ->orWhereHas('project', fn ($p) => $p->where('name', 'like', $like))
                    ->orWhereHas('responsible', fn ($a) => $a->where('name', 'like', $like));
            });
        }

        if ($request->filled('assignee_id')) {
            $stagesQuery->where('responsible_id', (int) $request->query('assignee_id'));
        }

        if ($request->filled('project_id')) {
            if ($request->query('project_id') === 'none') {
                return collect();
            }
            $stagesQuery->where('project_id', (int) $request->query('project_id'));
        }

        return $stagesQuery->orderBy('deadline')->get()->map(function (ProjectStages $stage) {
            $steps = $stage->steps;
            $total = $steps->count();
            $done = $steps->where('result_status', 'done')->count();
            $percent = $total > 0 ? (int) round(($done / $total) * 100) : 0;
            $deadline = $stage->deadline
                ? Carbon::parse($stage->deadline)->startOfDay()
                : null;
            $isOverdue = $deadline
                && $percent < 100
                && $deadline->copy()->endOfDay()->isPast();

            $status = $percent >= 100
                ? DesignerTaskStatus::Completed->value
                : ($done > 0 || $isOverdue
                    ? DesignerTaskStatus::InProgress->value
                    : DesignerTaskStatus::New->value);

            $type = (string) $stage->stage_type;
            $labelKey = 'projects.stage_'.$type;
            $stageLabel = $type !== '' ? (string) __($labelKey) : '';
            if ($stageLabel === $labelKey) {
                $stageLabel = $type;
            }
            $customName = is_string($stage->name) ? trim($stage->name) : '';
            $title = $customName !== '' ? $customName : $stageLabel;

            return [
                'id' => 'checklist-'.$stage->id,
                'checklist_id' => (int) $stage->id,
                'source_type' => 'checklist',
                'title' => $title,
                'description' => null,
                'status' => $status,
                'status_label' => __('tasks.status_'.$status),
                'due_at' => $deadline?->toIso8601String(),
                'due_at_label' => $deadline?->format('d.m.Y'),
                'completed_at' => null,
                'creator_id' => $stage->created_by ? (int) $stage->created_by : null,
                'creator_name' => null,
                'assignee_id' => $stage->responsible_id ? (int) $stage->responsible_id : null,
                'assignee_name' => $stage->responsible?->name,
                'project_id' => (int) $stage->project_id,
                'project_name' => $stage->project?->name,
                'is_overdue' => $isOverdue,
                'is_due_today' => $deadline?->isToday() ?? false,
                'can_edit' => false,
                'can_change_status' => false,
                'can_delete' => false,
                'draggable' => false,
            ];
        })->filter(function (array $card) use ($request) {
            if ($request->boolean('overdue') && empty($card['is_overdue'])) {
                return false;
            }
            if ($status = $request->query('status')) {
                $statuses = is_array($status) ? $status : explode(',', (string) $status);
                if ($statuses !== [] && ! in_array($card['status'], $statuses, true)) {
                    return false;
                }
            }

            return true;
        })->values();
    }

    public function store(DesignerTaskSaveRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $task = new DesignerTask;
        $task->creator_id = (int) $user->id;
        $task->assignee_id = (int) ($data['assignee_id'] ?? $user->id);
        $task->project_id = $data['project_id'] ?? null;
        $task->team_id = WorkspaceAccess::activeTeamId($user);
        $task->title = trim((string) $data['title']);
        $task->description = isset($data['description']) ? trim((string) $data['description']) : null;
        $task->due_at = $data['due_at'];
        $status = DesignerTaskStatus::tryFrom((string) ($data['status'] ?? DesignerTaskStatus::New->value))
            ?? DesignerTaskStatus::New;
        $task->applyStatus($status);
        $task->save();

        $this->notifier->notifyTaskAssigned($user, null, (int) $task->assignee_id, $task->fresh(['project']));

        return response()->json([
            'success' => true,
            'message' => __('tasks.created'),
            'task' => $this->payload($task->fresh(['creator', 'assignee', 'project']), $user),
        ], 201);
    }

    public function show(Request $request, DesignerTask $task): JsonResponse
    {
        $user = $request->user();
        abort_unless(WorkspaceAccess::canAccessDesignerTask($user, $task), 403);

        $task->load(['creator:id,name,email', 'assignee:id,name,email', 'project:id,name']);

        return response()->json([
            'success' => true,
            'task' => $this->payload($task, $user),
        ]);
    }

    public function update(DesignerTaskSaveRequest $request, DesignerTask $task): JsonResponse
    {
        $user = $request->user();
        abort_unless(WorkspaceAccess::canFullyEditDesignerTask($user, $task), 403);

        $data = $request->validated();
        $previousAssignee = (int) $task->assignee_id;

        $task->title = trim((string) $data['title']);
        $task->description = isset($data['description']) ? trim((string) $data['description']) : null;
        $task->assignee_id = (int) ($data['assignee_id'] ?? $user->id);
        $task->project_id = $data['project_id'] ?? null;
        $task->due_at = $data['due_at'];

        if (! empty($data['status'])) {
            $status = DesignerTaskStatus::tryFrom((string) $data['status']) ?? $task->status;
            $task->applyStatus($status);
        }

        $task->save();

        $this->notifier->notifyTaskAssigned(
            $user,
            $previousAssignee,
            (int) $task->assignee_id,
            $task->fresh(['project'])
        );

        return response()->json([
            'success' => true,
            'message' => __('tasks.updated'),
            'task' => $this->payload($task->fresh(['creator', 'assignee', 'project']), $user),
        ]);
    }

    public function updateStatus(Request $request, DesignerTask $task): JsonResponse
    {
        $user = $request->user();
        abort_unless(WorkspaceAccess::canChangeDesignerTaskStatus($user, $task), 403);

        $data = $request->validate([
            'status' => ['required', 'string', Rule::in(DesignerTaskStatus::values())],
        ]);

        $task->applyStatus(DesignerTaskStatus::from($data['status']));
        $task->save();

        return response()->json([
            'success' => true,
            'message' => __('tasks.status_updated'),
            'task' => $this->payload($task->fresh(['creator', 'assignee', 'project']), $user),
        ]);
    }

    public function destroy(Request $request, DesignerTask $task): JsonResponse
    {
        $user = $request->user();
        abort_unless(WorkspaceAccess::canFullyEditDesignerTask($user, $task), 403);

        $task->delete();

        return response()->json([
            'success' => true,
            'message' => __('tasks.deleted'),
        ]);
    }

    /** @return array<string, mixed> */
    private function payload(DesignerTask $task, $user): array
    {
        $status = $task->status instanceof DesignerTaskStatus
            ? $task->status
            : DesignerTaskStatus::tryFrom((string) $task->status);

        return [
            'id' => (int) $task->id,
            'source_type' => 'designer_task',
            'title' => (string) $task->title,
            'description' => $task->description,
            'status' => $status?->value ?? 'new',
            'status_label' => $status?->label() ?? '',
            'due_at' => $task->due_at?->toIso8601String(),
            'due_at_label' => $task->due_at?->timezone(config('app.timezone'))->format('d.m.Y H:i'),
            'completed_at' => $task->completed_at?->toIso8601String(),
            'created_at' => $task->created_at?->toIso8601String(),
            'updated_at' => $task->updated_at?->toIso8601String(),
            'creator_id' => (int) $task->creator_id,
            'creator_name' => $task->creator?->name,
            'assignee_id' => (int) $task->assignee_id,
            'assignee_name' => $task->assignee?->name,
            'project_id' => $task->project_id ? (int) $task->project_id : null,
            'project_name' => $task->project?->name,
            'team_id' => $task->team_id ? (int) $task->team_id : null,
            'is_overdue' => $task->isOverdue(),
            'is_due_today' => $task->isDueToday(),
            'can_edit' => WorkspaceAccess::canFullyEditDesignerTask($user, $task),
            'can_change_status' => WorkspaceAccess::canChangeDesignerTaskStatus($user, $task),
            'can_delete' => WorkspaceAccess::canFullyEditDesignerTask($user, $task),
        ];
    }
}
