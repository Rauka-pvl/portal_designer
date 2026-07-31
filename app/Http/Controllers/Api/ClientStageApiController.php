<?php

namespace App\Http\Controllers\Api;

use App\Enums\PipelineType;
use App\Http\Controllers\Api\Concerns\EnsuresDesigner;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DeletePipelineStageRequest;
use App\Http\Requests\Api\ReorderPipelineStagesRequest;
use App\Http\Requests\Api\StorePipelineStageRequest;
use App\Http\Requests\Api\UpdatePipelineStageRequest;
use App\Http\Resources\ProjectStageResource;
use App\Models\Client;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Services\Crm\PipelineService;
use App\Support\AccountPermissions;
use Illuminate\Http\Request;

class ClientStageApiController extends Controller
{
    use EnsuresDesigner;

    public function __construct(private PipelineService $pipelines) {}

    public function index(Request $request)
    {
        $this->ensureDesigner($request);
        $this->pipelines->ensureDefaultsForUser((int) $request->user()->id);
        $pipeline = Pipeline::defaultForUser((int) $request->user()->id, PipelineType::Client);

        return ProjectStageResource::collection($pipeline->stages()->orderBy('position')->get());
    }

    public function store(StorePipelineStageRequest $request)
    {
        $this->ensureDesigner($request);
        $this->authorizeManagement($request);
        $this->pipelines->ensureDefaultsForUser((int) $request->user()->id);
        $pipeline = Pipeline::defaultForUser((int) $request->user()->id, PipelineType::Client);
        $stage = $this->pipelines->addStage(
            $pipeline,
            $request->validated('name'),
            $request->validated('color')
        );

        return (new ProjectStageResource($stage))->response()->setStatusCode(201);
    }

    public function update(UpdatePipelineStageRequest $request, int $stageId)
    {
        $this->ensureDesigner($request);
        $this->authorizeManagement($request);
        $stage = $this->stageFor($request, $stageId);
        $stage->fill($request->validated())->save();

        return new ProjectStageResource($stage);
    }

    public function destroy(DeletePipelineStageRequest $request, int $stageId)
    {
        $this->ensureDesigner($request);
        $this->authorizeManagement($request);
        $stage = $this->stageFor($request, $stageId);
        $userId = (int) $request->user()->id;

        $target = ! empty($request->validated()['move_to_stage_id'])
            ? $stage->pipeline->stages()->findOrFail($request->validated()['move_to_stage_id'])
            : null;

        $cards = Client::query()
            ->where('user_id', $userId)
            ->where('status', $stage->system_key)
            ->count();

        if ($cards > 0 && ! $target) {
            return response()->json([
                'message' => 'Clients are in this stage. Provide move_to_stage_id.',
                'code' => 'move_required',
                'meta' => ['card_count' => $cards],
            ], 422);
        }

        $this->pipelines->deleteStage(
            $stage,
            $target,
            fn (string $from, string $to) => Client::query()
                ->where('user_id', $userId)
                ->where('status', $from)
                ->update(['status' => $to]),
            fn () => null
        );

        return response()->noContent();
    }

    public function reorder(ReorderPipelineStagesRequest $request)
    {
        $this->ensureDesigner($request);
        $this->authorizeManagement($request);
        $this->pipelines->ensureDefaultsForUser((int) $request->user()->id);
        $pipeline = Pipeline::defaultForUser((int) $request->user()->id, PipelineType::Client);
        $this->pipelines->reorderStages($pipeline, $request->validated()['stage_ids']);

        return ProjectStageResource::collection(
            $pipeline->fresh()->stages()->orderBy('position')->get()
        );
    }

    private function authorizeManagement(Request $request): void
    {
        abort_unless(AccountPermissions::canManageClientPipeline($request->user()), 403);
    }

    private function stageFor(Request $request, int $stageId): PipelineStage
    {
        return PipelineStage::query()
            ->whereHas('pipeline', fn ($q) => $q
                ->where('user_id', $request->user()->id)
                ->where('type', PipelineType::Client))
            ->with('pipeline')
            ->findOrFail($stageId);
    }
}
