<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\EnsuresDesigner;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreChecklistRequest;
use App\Http\Requests\Api\UpdateChecklistRequest;
use App\Http\Resources\Api\ChecklistResource;
use App\Models\Project;
use App\Models\ProjectStages;
use App\Services\Crm\ChecklistService;
use App\Support\WorkspaceAccess;
use Illuminate\Http\Request;

class ChecklistApiController extends Controller
{
    use EnsuresDesigner;

    public function __construct(private ChecklistService $checklists) {}

    public function index(Request $request, int $project)
    {
        $this->ensureDesigner($request);

        return ChecklistResource::collection($this->checklists->list($this->project($request, $project)));
    }

    public function store(StoreChecklistRequest $request, int $project)
    {
        $this->ensureDesigner($request);
        $checklist = $this->checklists->create($this->project($request, $project), (int) $request->user()->id, $request->validated());

        return (new ChecklistResource($checklist->load('steps')))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $checklist)
    {
        $this->ensureDesigner($request);

        return new ChecklistResource($this->stage($request, $checklist)->load('steps'));
    }

    public function update(UpdateChecklistRequest $request, int $checklist)
    {
        $this->ensureDesigner($request);

        return new ChecklistResource($this->checklists->update($this->stage($request, $checklist), (int) $request->user()->id, $request->validated())->load('steps'));
    }

    public function destroy(Request $request, int $checklist)
    {
        $this->ensureDesigner($request);
        $this->checklists->delete($this->stage($request, $checklist));

        return response()->noContent();
    }

    public function results(Request $request, int $project)
    {
        $this->ensureDesigner($request);

        return response()->json([
            'data' => $this->checklists->resultsGrouped($this->project($request, $project)),
        ]);
    }

    private function project(Request $request, int $id): Project
    {
        return WorkspaceAccess::scopeProjects(Project::query(), $request->user())->findOrFail($id);
    }

    private function stage(Request $request, int $id): ProjectStages
    {
        $stage = ProjectStages::query()->with('project')->findOrFail($id);
        $this->project($request, (int) $stage->project_id);

        return $stage;
    }
}
