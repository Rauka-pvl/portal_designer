<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTaskRequest;
use App\Http\Requests\Api\UpdateTaskRequest;
use App\Http\Requests\Api\UpdateTaskStatusRequest;
use App\Http\Resources\TaskCollection;
use App\Http\Resources\TaskResource;
use App\Models\DesignerTask;
use App\Services\Crm\TaskService;
use App\Support\WorkspaceAccess;
use Illuminate\Http\Request;

class TaskApiController extends Controller
{
    public function __construct(private TaskService $tasks) {}

    public function index(Request $request): TaskCollection
    {
        $query = WorkspaceAccess::scopeDesignerTasks(DesignerTask::query(), $request->user())
            ->with(['creator:id,name', 'assignee:id,name', 'project:id,name'])->orderBy('due_at');
        if ($request->filled('project_id')) $request->query('project_id') === 'none' ? $query->whereNull('project_id') : $query->where('project_id', $request->integer('project_id'));
        if ($request->filled('assignee_id')) $query->where('assignee_id', $request->integer('assignee_id'));
        return new TaskCollection($query->paginate($request->integer('per_page', 20)));
    }

    public function store(StoreTaskRequest $request): TaskResource
    {
        return new TaskResource($this->load($this->tasks->save($request->user(), $request->validated())));
    }

    public function show(Request $request, int $taskId): TaskResource
    {
        return new TaskResource($this->load($this->tasks->findAccessible($request->user(), $taskId)));
    }

    public function update(UpdateTaskRequest $request, int $taskId): TaskResource
    {
        $task = $this->tasks->findAccessible($request->user(), $taskId);
        abort_unless(WorkspaceAccess::canFullyEditDesignerTask($request->user(), $task), 403);
        return new TaskResource($this->load($this->tasks->save($request->user(), $request->validated(), $task)));
    }

    public function destroy(Request $request, int $taskId)
    {
        $task = $this->tasks->findAccessible($request->user(), $taskId);
        abort_unless(WorkspaceAccess::canFullyEditDesignerTask($request->user(), $task), 403);
        $task->delete();
        return response()->json([], 204);
    }

    public function updateStatus(UpdateTaskStatusRequest $request, int $taskId): TaskResource
    {
        $task = $this->tasks->findAccessible($request->user(), $taskId);
        abort_unless(WorkspaceAccess::canChangeDesignerTaskStatus($request->user(), $task), 403);
        return new TaskResource($this->load($this->tasks->updateStatus($task, $request->validated()['status'])));
    }

    public function kanban(Request $request)
    {
        $cards = $this->tasks->kanbanCards($request->user(), $request);
        $columns = ['new', 'in_progress', 'completed', 'cancelled'];
        $data = [];
        foreach ($columns as $status) {
            $items = $cards->filter(function ($card) use ($status) {
                $value = is_array($card) ? ($card['status'] ?? null) : ($card->status?->value ?? (string) $card->status);

                return $value === $status;
            })->values()->map(fn ($card) => (new TaskResource($card))->resolve($request));
            $data[$status] = ['count' => $items->count(), 'items' => $items->all()];
        }

        return response()->json(['data' => $data]);
    }

    public function calendar(Request $request)
    {
        $start = $request->query('date_from', $request->query('start', now()->startOfMonth()->toDateString()));
        $end = $request->query('date_to', $request->query('end', now()->endOfMonth()->toDateString()));

        return response()->json([
            'data' => $this->tasks->calendarEvents($request->user(), (string) $start, (string) $end),
            'meta' => ['date_from' => $start, 'date_to' => $end],
        ]);
    }

    private function load(DesignerTask $task): DesignerTask
    {
        return $task->fresh(['creator:id,name', 'assignee:id,name', 'project:id,name']);
    }
}
