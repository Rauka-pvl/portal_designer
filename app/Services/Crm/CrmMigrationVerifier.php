<?php

namespace App\Services\Crm;

use App\Enums\ProjectStatus;
use App\Enums\SupplyStatus;
use App\Models\ActivityEvent;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Project;
use App\Models\ProjectObjectDetail;
use App\Models\Supplier_orders;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CrmMigrationVerifier
{
    /**
     * @return array{ok: bool, checks: list<array{name: string, expected: int|string, actual: int|string, ok: bool, note?: string}>}
     */
    public function verify(): array
    {
        $checks = [];

        $designerCount = User::query()->where('account_type', 'designer')->count();
        $projectPipelines = Pipeline::query()->where('type', 'project')->count();
        $supplyPipelines = Pipeline::query()->where('type', 'supply')->count();

        $checks[] = $this->row('Designer users', $designerCount, $designerCount);
        $checks[] = $this->row('Project pipelines', $designerCount, $projectPipelines, 'One default project pipeline per designer');
        $checks[] = $this->row('Supply pipelines', $designerCount, $supplyPipelines, 'One default supply pipeline per designer');

        $expectedProjectStages = $designerCount * count(ProjectStatus::funnelOrder());
        $actualProjectStages = PipelineStage::query()
            ->whereHas('pipeline', fn ($q) => $q->where('type', 'project'))
            ->count();
        $checks[] = $this->row('Project pipeline stages', $expectedProjectStages, $actualProjectStages);

        $expectedSupplyStages = $designerCount * count(SupplyStatus::funnelOrder());
        $actualSupplyStages = PipelineStage::query()
            ->whereHas('pipeline', fn ($q) => $q->where('type', 'supply'))
            ->count();
        $checks[] = $this->row('Supply pipeline stages', $expectedSupplyStages, $actualSupplyStages);

        $projectsWithObject = Project::query()->whereNotNull('object_id')->count();
        $detailsCount = ProjectObjectDetail::query()->count();
        $checks[] = $this->row(
            'project_object_details vs projects.object_id',
            $projectsWithObject,
            $detailsCount,
            'Details may be fewer if passport missing; investigate skips'
        );

        $orphanDetails = ProjectObjectDetail::query()
            ->whereDoesntHave('project')
            ->count();
        $checks[] = $this->row('Orphan project_object_details', 0, $orphanDetails);

        $activityCreated = ActivityEvent::query()->where('event_type', 'project.created')->count();
        $projectCount = Project::query()->count();
        $checks[] = $this->row('project.created activity events', $projectCount, $activityCreated);

        $ok = collect($checks)->every(fn ($c) => $c['ok']);

        return ['ok' => $ok, 'checks' => $checks];
    }

    private function row(string $name, int|string $expected, int|string $actual, ?string $note = null): array
    {
        return [
            'name' => $name,
            'expected' => $expected,
            'actual' => $actual,
            'ok' => $expected === $actual,
            'note' => $note,
        ];
    }
}
