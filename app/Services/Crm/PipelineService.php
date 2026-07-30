<?php

namespace App\Services\Crm;

use App\Enums\PipelineType;
use App\Enums\ProjectStatus;
use App\Enums\SupplyStatus;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use Illuminate\Support\Facades\DB;

class PipelineService
{
    public function ensureDefaultsForUser(int $userId): void
    {
        if (! Pipeline::query()->where('user_id', $userId)->where('type', PipelineType::Project)->exists()) {
            $this->createProjectPipeline($userId);
        }

        if (! Pipeline::query()->where('user_id', $userId)->where('type', PipelineType::Supply)->exists()) {
            $this->createSupplyPipeline($userId);
        }
    }

    public function createProjectPipeline(int $userId, string $name = 'Общая воронка'): Pipeline
    {
        return DB::transaction(function () use ($userId, $name) {
            $pipeline = Pipeline::query()->create([
                'user_id' => $userId,
                'type' => PipelineType::Project,
                'name' => $name,
                'is_default' => true,
            ]);

            $position = 0;
            foreach (ProjectStatus::funnelOrder() as $status) {
                PipelineStage::query()->create([
                    'pipeline_id' => $pipeline->id,
                    'system_key' => $status->value,
                    'name' => $status->label(),
                    'color' => $status->defaultColor(),
                    'position' => $position++,
                    'is_system' => true,
                    'is_active' => true,
                ]);
            }

            return $pipeline->load('stages');
        });
    }

    public function createSupplyPipeline(int $userId, string $name = 'Воронка поставок'): Pipeline
    {
        return DB::transaction(function () use ($userId, $name) {
            $pipeline = Pipeline::query()->create([
                'user_id' => $userId,
                'type' => PipelineType::Supply,
                'name' => $name,
                'is_default' => true,
            ]);

            $position = 0;
            foreach (SupplyStatus::funnelOrder() as $status) {
                PipelineStage::query()->create([
                    'pipeline_id' => $pipeline->id,
                    'system_key' => $status->value,
                    'name' => $status->label(),
                    'color' => $status->defaultColor(),
                    'position' => $position++,
                    'is_system' => true,
                    'is_active' => true,
                ]);
            }

            return $pipeline->load('stages');
        });
    }

    public function addStage(Pipeline $pipeline, string $name, ?string $color = null, ?string $systemKey = null): PipelineStage
    {
        $maxPosition = (int) $pipeline->stages()->max('position');
        $key = $systemKey ?: ('custom_'.str()->lower(str()->random(8)));

        return PipelineStage::query()->create([
            'pipeline_id' => $pipeline->id,
            'system_key' => $key,
            'name' => $name,
            'color' => $color ?: '#64748b',
            'position' => $maxPosition + 1,
            'is_system' => false,
            'is_active' => true,
        ]);
    }

    /**
     * @param  list<int>  $orderedStageIds
     */
    public function reorderStages(Pipeline $pipeline, array $orderedStageIds): void
    {
        DB::transaction(function () use ($pipeline, $orderedStageIds) {
            foreach ($orderedStageIds as $position => $stageId) {
                PipelineStage::query()
                    ->where('pipeline_id', $pipeline->id)
                    ->whereKey($stageId)
                    ->update(['position' => $position]);
            }
        });
    }

    /**
     * Delete stage; if cards exist, move them to $targetSystemKey first.
     *
     * @param  callable(string $fromKey, string $toKey): int  $moveCards  returns moved count
     */
    public function deleteStage(
        PipelineStage $stage,
        ?PipelineStage $target,
        callable $moveCards,
        callable $recordHistory
    ): void {
        DB::transaction(function () use ($stage, $target, $moveCards, $recordHistory) {
            $pipeline = $stage->pipeline;

            if ($target) {
                $moved = $moveCards($stage->system_key, $target->system_key);
                $recordHistory($stage, $target, $moved);
            }

            $stage->delete();

            $remaining = $pipeline->stages()->orderBy('position')->get();
            foreach ($remaining as $i => $row) {
                $row->update(['position' => $i]);
            }
        });
    }
}
