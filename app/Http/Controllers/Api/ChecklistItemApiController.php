<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\EnsuresDesigner;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ChecklistItemResultRequest;
use App\Http\Requests\Api\CompleteChecklistItemRequest;
use App\Http\Requests\Api\ReorderChecklistItemsRequest;
use App\Http\Requests\Api\StoreChecklistItemRequest;
use App\Http\Requests\Api\UpdateChecklistItemRequest;
use App\Http\Resources\Api\ChecklistItemResource;
use App\Models\ProjectStages;
use App\Models\ProjectStageStep;
use App\Services\Crm\ChecklistService;
use App\Support\WorkspaceAccess;
use Illuminate\Http\Request;

class ChecklistItemApiController extends Controller
{
    use EnsuresDesigner;

    public function __construct(private ChecklistService $checklists) {}

    public function store(StoreChecklistItemRequest $request, int $checklist)
    {
        $this->ensureDesigner($request);
        $item = $this->checklists->createItem($this->stage($request, $checklist), $request->validated());

        return (new ChecklistItemResource($item))->response()->setStatusCode(201);
    }

    public function update(UpdateChecklistItemRequest $request, int $item)
    {
        $this->ensureDesigner($request);

        return new ChecklistItemResource($this->checklists->updateItem($this->item($request, $item), $request->validated()));
    }

    public function destroy(Request $request, int $item)
    {
        $this->ensureDesigner($request);
        $this->item($request, $item)->delete();

        return response()->noContent();
    }

    public function reorder(ReorderChecklistItemsRequest $request)
    {
        $this->ensureDesigner($request);
        $ids = $request->validated('item_ids') ?? $request->validated('items') ?? [];
        if (isset($ids[0]['id'])) {
            $ids = collect($ids)->sortBy('position')->pluck('id')->all();
        }
        $first = ProjectStageStep::query()->findOrFail((int) ($ids[0] ?? 0));
        $stage = $this->stage($request, (int) $first->project_stage_id);

        return ChecklistItemResource::collection($this->checklists->reorder($stage, array_map('intval', $ids)));
    }

    public function complete(CompleteChecklistItemRequest $request, int $item)
    {
        $this->ensureDesigner($request);
        $done = $request->boolean('completed', $request->boolean('done'));

        return new ChecklistItemResource($this->checklists->completeItem($this->item($request, $item), $done));
    }

    public function result(ChecklistItemResultRequest $request, int $item)
    {
        $this->ensureDesigner($request);
        $data = $request->validated();
        $text = $data['result'] ?? $data['result_comment'] ?? null;

        return new ChecklistItemResource($this->checklists->setItemResult($this->item($request, $item), $text));
    }

    private function stage(Request $request, int $id): ProjectStages
    {
        $stage = ProjectStages::query()->with('project')->findOrFail($id);
        abort_unless($stage->project && WorkspaceAccess::canAccessProject($request->user(), $stage->project), 404);

        return $stage;
    }

    private function item(Request $request, int $id): ProjectStageStep
    {
        $item = ProjectStageStep::query()->with('stage.project')->findOrFail($id);
        $this->stage($request, (int) $item->project_stage_id);

        return $item;
    }
}
