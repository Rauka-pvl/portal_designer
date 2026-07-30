<?php

namespace App\Http\Controllers\Designer;

use App\Enums\PipelineType;
use App\Http\Controllers\Controller;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Project;
use App\Models\Supplier_orders;
use App\Services\Crm\ActivityFeedService;
use App\Services\Crm\PipelineService;
use App\Support\AccountPermissions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PipelineController extends Controller
{
    public function __construct(
        private PipelineService $pipelines,
        private ActivityFeedService $activity,
    ) {}

    public function showProjectPipeline(Request $request)
    {
        $user = $request->user();
        $this->pipelines->ensureDefaultsForUser((int) $user->id);
        $pipeline = Pipeline::defaultForUser((int) $user->id, PipelineType::Project);

        return response()->json([
            'pipeline' => $this->pipelinePayload($pipeline),
            'can_manage' => AccountPermissions::canManageProjectPipeline($user),
        ]);
    }

    public function showSupplyPipeline(Request $request)
    {
        $user = $request->user();
        $this->pipelines->ensureDefaultsForUser((int) $user->id);
        $pipeline = Pipeline::defaultForUser((int) $user->id, PipelineType::Supply);

        return response()->json([
            'pipeline' => $this->pipelinePayload($pipeline),
            'can_manage' => AccountPermissions::canManageSupplyPipeline($user),
        ]);
    }

    public function storeStage(Request $request)
    {
        $user = $request->user();
        abort_unless(AccountPermissions::canManageProjectPipeline($user), 403);

        $data = $request->validate([
            'type' => ['required', Rule::in(['project', 'supply'])],
            'name' => ['required', 'string', 'max:120'],
            'color' => ['nullable', 'string', 'max:32'],
        ]);

        $this->pipelines->ensureDefaultsForUser((int) $user->id);
        $pipeline = Pipeline::defaultForUser((int) $user->id, PipelineType::from($data['type']));
        $stage = $this->pipelines->addStage($pipeline, $data['name'], $data['color'] ?? null);

        return response()->json(['success' => true, 'stage' => $stage]);
    }

    public function updateStage(Request $request, int $stageId)
    {
        $user = $request->user();
        abort_unless(AccountPermissions::canManageProjectPipeline($user), 403);

        $stage = PipelineStage::query()
            ->whereHas('pipeline', fn ($q) => $q->where('user_id', $user->id))
            ->findOrFail($stageId);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'color' => ['sometimes', 'nullable', 'string', 'max:32'],
        ]);

        $stage->fill($data)->save();

        return response()->json(['success' => true, 'stage' => $stage]);
    }

    public function reorder(Request $request)
    {
        $user = $request->user();
        abort_unless(AccountPermissions::canManageProjectPipeline($user), 403);

        $data = $request->validate([
            'type' => ['required', Rule::in(['project', 'supply'])],
            'stage_ids' => ['required', 'array', 'min:1'],
            'stage_ids.*' => ['integer'],
        ]);

        $pipeline = Pipeline::defaultForUser((int) $user->id, PipelineType::from($data['type']));
        $this->pipelines->reorderStages($pipeline, $data['stage_ids']);

        return response()->json(['success' => true]);
    }

    public function destroyStage(Request $request, int $stageId)
    {
        $user = $request->user();
        abort_unless(AccountPermissions::canManageProjectPipeline($user), 403);

        $stage = PipelineStage::query()
            ->whereHas('pipeline', fn ($q) => $q->where('user_id', $user->id))
            ->with('pipeline')
            ->findOrFail($stageId);

        $data = $request->validate([
            'target_stage_id' => ['nullable', 'integer'],
        ]);

        $cardCount = $this->countCards($stage);

        if ($cardCount > 0 && empty($data['target_stage_id'])) {
            return response()->json([
                'success' => false,
                'message' => __('projects.pipeline_move_required'),
                'card_count' => $cardCount,
            ], 422);
        }

        $target = null;
        if (! empty($data['target_stage_id'])) {
            $target = PipelineStage::query()
                ->where('pipeline_id', $stage->pipeline_id)
                ->whereKey($data['target_stage_id'])
                ->firstOrFail();
        }

        if ($cardCount === 0 && ! $request->boolean('confirm')) {
            return response()->json([
                'success' => false,
                'needs_confirm' => true,
                'message' => __('projects.pipeline_delete_confirm'),
            ], 422);
        }

        $this->pipelines->deleteStage(
            $stage,
            $target,
            function (string $fromKey, string $toKey) use ($stage, $user) {
                return $this->moveCards($stage->pipeline->type->value, (int) $user->id, $fromKey, $toKey);
            },
            function (PipelineStage $from, ?PipelineStage $to, int $moved) use ($user) {
                $this->activity->record(
                    (int) $user->id,
                    'pipeline',
                    (int) $from->pipeline_id,
                    'pipeline.stage_deleted',
                    $user,
                    null,
                    [
                        'from' => $from->system_key,
                        'to' => $to?->system_key,
                        'moved' => $moved,
                    ]
                );
            }
        );

        return response()->json(['success' => true]);
    }

    private function countCards(PipelineStage $stage): int
    {
        $userId = (int) $stage->pipeline->user_id;
        $key = $stage->system_key;

        return match ($stage->pipeline->type) {
            PipelineType::Project => Project::query()->where('user_id', $userId)->where('status', $key)->count(),
            PipelineType::Supply => Supplier_orders::query()->where('user_id', $userId)->where('status', $key)->count(),
        };
    }

    private function moveCards(string $type, int $userId, string $fromKey, string $toKey): int
    {
        if ($type === 'project') {
            return Project::query()
                ->where('user_id', $userId)
                ->where('status', $fromKey)
                ->update(['status' => $toKey]);
        }

        return Supplier_orders::query()
            ->where('user_id', $userId)
            ->where('status', $fromKey)
            ->update(['status' => $toKey]);
    }

    private function pipelinePayload(Pipeline $pipeline): array
    {
        return [
            'id' => $pipeline->id,
            'name' => $pipeline->name,
            'type' => $pipeline->type->value,
            'stages' => $pipeline->stages->map(fn (PipelineStage $s) => [
                'id' => $s->id,
                'system_key' => $s->system_key,
                'name' => $s->name,
                'color' => $s->color,
                'position' => $s->position,
                'is_system' => $s->is_system,
            ])->values(),
        ];
    }
}
