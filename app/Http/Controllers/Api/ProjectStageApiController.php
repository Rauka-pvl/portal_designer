<?php

namespace App\Http\Controllers\Api;

use App\Enums\PipelineType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DeletePipelineStageRequest;
use App\Http\Requests\Api\ReorderPipelineStagesRequest;
use App\Http\Requests\Api\StorePipelineStageRequest;
use App\Http\Requests\Api\UpdatePipelineStageRequest;
use App\Http\Resources\ProjectStageResource;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Project;
use App\Services\Crm\PipelineService;
use App\Support\AccountPermissions;
use Illuminate\Http\Request;

class ProjectStageApiController extends Controller
{
    public function __construct(private PipelineService $pipelines) {}

    public function index(Request $request)
    {
        $this->pipelines->ensureDefaultsForUser($request->user()->id);
        $pipeline = Pipeline::defaultForUser($request->user()->id, PipelineType::Project);
        return ProjectStageResource::collection($pipeline->stages()->orderBy('position')->get());
    }

    public function store(StorePipelineStageRequest $request): ProjectStageResource
    {
        $this->authorizeManagement($request);
        $this->pipelines->ensureDefaultsForUser($request->user()->id);
        $pipeline = Pipeline::defaultForUser($request->user()->id, PipelineType::Project);
        return new ProjectStageResource($this->pipelines->addStage($pipeline, $request->validated()['name'], $request->validated()['color'] ?? null));
    }

    public function update(UpdatePipelineStageRequest $request, int $stageId): ProjectStageResource
    {
        $this->authorizeManagement($request);
        $stage = $this->stageFor($request, $stageId);
        $stage->fill($request->validated())->save();
        return new ProjectStageResource($stage);
    }

    public function destroy(DeletePipelineStageRequest $request, int $stageId)
    {
        $this->authorizeManagement($request);
        $stage = $this->stageFor($request, $stageId);
        $target = ! empty($request->validated()['move_to_stage_id']) ? $stage->pipeline->stages()->findOrFail($request->validated()['move_to_stage_id']) : null;
        $cards = Project::query()->where('user_id', $request->user()->id)->where('status', $stage->system_key)->count();
        if ($cards && ! $target) return response()->json(['data' => null, 'meta' => ['card_count' => $cards, 'move_required' => true]], 422);
        $this->pipelines->deleteStage($stage, $target,
            fn ($from, $to) => Project::query()->where('user_id', $request->user()->id)->where('status', $from)->update(['status' => $to]),
            fn () => null);
        return response()->json([], 204);
    }

    public function reorder(ReorderPipelineStagesRequest $request)
    {
        $this->authorizeManagement($request);
        $pipeline = Pipeline::defaultForUser($request->user()->id, PipelineType::Project);
        $this->pipelines->reorderStages($pipeline, $request->validated()['stage_ids']);
        return ProjectStageResource::collection($pipeline->fresh()->stages()->orderBy('position')->get());
    }

    private function authorizeManagement(Request $request): void
    {
        abort_unless(AccountPermissions::canManageProjectPipeline($request->user()), 403);
    }

    private function stageFor(Request $request, int $stageId): PipelineStage
    {
        return PipelineStage::query()->whereHas('pipeline', fn ($q) => $q->where('user_id', $request->user()->id)->where('type', PipelineType::Project))->with('pipeline')->findOrFail($stageId);
    }
}
