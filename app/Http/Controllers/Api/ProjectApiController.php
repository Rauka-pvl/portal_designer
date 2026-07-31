<?php

namespace App\Http\Controllers\Api;

use App\Enums\PipelineType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreProjectCommentRequest;
use App\Http\Requests\Api\StoreProjectRequest;
use App\Http\Requests\Api\UpdateProjectRequest;
use App\Http\Requests\Api\UpdateProjectStageRequest;
use App\Http\Resources\ActivityEventResource;
use App\Http\Resources\ProjectCollection;
use App\Http\Resources\ProjectResource;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Project;
use App\Services\Crm\ActivityFeedService;
use App\Services\Crm\PipelineService;
use App\Services\Crm\ProjectService;
use App\Support\WorkspaceAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectApiController extends Controller
{
    public function __construct(private ProjectService $projects, private PipelineService $pipelines, private ActivityFeedService $activity) {}

    public function index(Request $request): ProjectCollection
    {
        $this->pipelines->ensureDefaultsForUser($request->user()->id);
        return new ProjectCollection(WorkspaceAccess::scopeProjects(Project::query(), $request->user())
            ->with(['client:id,full_name', 'user:id,name', 'objectDetails.client:id,full_name', 'stages.steps'])
            ->withCount('stages')->orderByDesc('id')->paginate($request->integer('per_page', 20)));
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = new Project(['user_id' => $request->user()->id]);
        WorkspaceAccess::attachTeamOnCreate($request->user(), $project);
        $this->projects->fillAndSave($request, $project);
        $project->update(['moderation_status' => 'approved', 'moderation_reason' => null]);
        $this->activity->record($request->user()->id, 'project', $project->id, 'project.created', $request->user(), null, ['name' => $project->name]);
        return (new ProjectResource($this->load($project)))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $projectId): ProjectResource
    {
        return new ProjectResource($this->load($this->projects->findAccessible($request->user(), $projectId)));
    }

    public function update(UpdateProjectRequest $request, int $projectId): ProjectResource
    {
        $project = $this->projects->findAccessible($request->user(), $projectId);
        $this->projects->fillAndSave($request, $project);
        $this->activity->record($request->user()->id, 'project', $project->id, 'project.updated', $request->user());
        return new ProjectResource($this->load($project));
    }

    public function destroy(Request $request, int $projectId)
    {
        $project = $this->projects->findAccessible($request->user(), $projectId);
        $this->projects->destroy($project);
        return response()->json([], 204);
    }

    public function updateStage(UpdateProjectStageRequest $request, int $projectId): ProjectResource
    {
        $project = $this->projects->findAccessible($request->user(), $projectId);
        $this->pipelines->ensureDefaultsForUser($request->user()->id);
        $pipeline = Pipeline::defaultForUser($request->user()->id, PipelineType::Project);
        $stage = ! empty($request->validated()['stage_id'])
            ? $pipeline->stages()->findOrFail($request->validated()['stage_id'])
            : $pipeline->stages()->where('system_key', $request->validated()['status'] ?? '')->firstOrFail();
        $from = $project->status;
        $project->update(['status' => $stage->system_key]);
        $this->activity->record($request->user()->id, 'project', $project->id, 'project.status_changed', $request->user(), null, ['from' => $from, 'to' => $stage->system_key]);
        return new ProjectResource($this->load($project));
    }

    public function activity(Request $request, int $projectId)
    {
        $project = $this->projects->findAccessible($request->user(), $projectId);
        return ActivityEventResource::collection($this->activity->forSubject('project', $project->id, $request->query('filter'), $request->integer('per_page', 30)));
    }

    public function comments(Request $request, int $projectId)
    {
        $project = $this->projects->findAccessible($request->user(), $projectId);
        return ActivityEventResource::collection($this->activity->forSubject('project', $project->id, 'comments', $request->integer('per_page', 30)));
    }

    public function storeComment(StoreProjectCommentRequest $request, int $projectId): ActivityEventResource
    {
        $project = $this->projects->findAccessible($request->user(), $projectId);
        return new ActivityEventResource($this->activity->record($project->user_id, 'project', $project->id, 'comment', $request->user(), $request->validated()['body']));
    }

    private function load(Project $project): Project
    {
        return $project->fresh(['client:id,full_name', 'user:id,name', 'objectDetails.client:id,full_name', 'stages.steps']);
    }
}
