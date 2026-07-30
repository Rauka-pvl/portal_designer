<?php

namespace App\Http\Controllers\Designer;

use App\Enums\PipelineType;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Project;
use App\Models\Supplier_orders;
use App\Models\User;
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

    private function pipelineTypes(): array
    {
        return ['project', 'supply', 'client'];
    }

    private function canManageType(?User $user, string $type): bool
    {
        return match ($type) {
            'supply' => AccountPermissions::canManageSupplyPipeline($user),
            'client' => AccountPermissions::canManageClientPipeline($user),
            default => AccountPermissions::canManageProjectPipeline($user),
        };
    }

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

    public function showClientPipeline(Request $request)
    {
        $user = $request->user();
        $this->pipelines->ensureDefaultsForUser((int) $user->id);
        $pipeline = Pipeline::defaultForUser((int) $user->id, PipelineType::Client);

        return response()->json([
            'pipeline' => $this->pipelinePayload($pipeline),
            'can_manage' => AccountPermissions::canManageClientPipeline($user),
        ]);
    }

    public function storeStage(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'type' => ['required', Rule::in($this->pipelineTypes())],
            'name' => ['required', 'string', 'max:120'],
            'color' => ['nullable', 'string', 'max:32'],
        ]);

        abort_unless($this->canManageType($user, $data['type']), 403);

        $this->pipelines->ensureDefaultsForUser((int) $user->id);
        $pipeline = Pipeline::defaultForUser((int) $user->id, PipelineType::from($data['type']));
        $stage = $this->pipelines->addStage($pipeline, $data['name'], $data['color'] ?? null);

        return response()->json(['success' => true, 'stage' => $stage]);
    }

    public function updateStage(Request $request, int $stageId)
    {
        $user = $request->user();

        $stage = PipelineStage::query()
            ->whereHas('pipeline', fn ($q) => $q->where('user_id', $user->id))
            ->with('pipeline')
            ->findOrFail($stageId);

        abort_unless($this->canManageType($user, $stage->pipeline->type->value), 403);

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

        $data = $request->validate([
            'type' => ['required', Rule::in($this->pipelineTypes())],
            'stage_ids' => ['required', 'array', 'min:1'],
            'stage_ids.*' => ['integer'],
        ]);

        abort_unless($this->canManageType($user, $data['type']), 403);

        $pipeline = Pipeline::defaultForUser((int) $user->id, PipelineType::from($data['type']));
        $this->pipelines->reorderStages($pipeline, $data['stage_ids']);

        return response()->json(['success' => true]);
    }

    public function sync(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'type' => ['required', Rule::in($this->pipelineTypes())],
            'stages' => ['required', 'array', 'min:1'],
            'stages.*.id' => ['nullable', 'integer'],
            'stages.*.name' => ['required', 'string', 'max:120'],
            'stages.*.color' => ['nullable', 'string', 'max:32'],
            'deletions' => ['nullable', 'array'],
            'deletions.*.id' => ['required', 'integer'],
            'deletions.*.target_stage_id' => ['nullable', 'integer'],
        ]);

        abort_unless($this->canManageType($user, $data['type']), 403);

        $this->pipelines->ensureDefaultsForUser((int) $user->id);
        $pipeline = Pipeline::defaultForUser((int) $user->id, PipelineType::from($data['type']));

        $synced = $this->pipelines->syncPipeline(
            $pipeline,
            $data['stages'],
            $data['deletions'] ?? [],
            fn (PipelineStage $stage) => $this->countCards($stage),
            function (string $fromKey, string $toKey) use ($pipeline, $user) {
                return $this->moveCards($pipeline->type->value, (int) $user->id, $fromKey, $toKey);
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

        return response()->json([
            'success' => true,
            'pipeline' => $this->pipelinePayload($synced),
        ]);
    }

    public function destroyStage(Request $request, int $stageId)
    {
        $user = $request->user();

        $stage = PipelineStage::query()
            ->whereHas('pipeline', fn ($q) => $q->where('user_id', $user->id))
            ->with('pipeline')
            ->findOrFail($stageId);

        abort_unless($this->canManageType($user, $stage->pipeline->type->value), 403);

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
            PipelineType::Client => Client::query()->where('user_id', $userId)->where('status', $key)->count(),
        };
    }

    private function moveCards(string $type, int $userId, string $fromKey, string $toKey): int
    {
        return match ($type) {
            'project' => Project::query()
                ->where('user_id', $userId)
                ->where('status', $fromKey)
                ->update(['status' => $toKey]),
            'supply' => Supplier_orders::query()
                ->where('user_id', $userId)
                ->where('status', $fromKey)
                ->update(['status' => $toKey]),
            'client' => Client::query()
                ->where('user_id', $userId)
                ->where('status', $fromKey)
                ->update(['status' => $toKey]),
            default => 0,
        };
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
