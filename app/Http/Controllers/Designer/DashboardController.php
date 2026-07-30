<?php

namespace App\Http\Controllers\Designer;

use App\Http\Controllers\Controller;
use App\Services\Crm\DashboardAnalyticsService;
use App\Services\Crm\PipelineService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardAnalyticsService $analytics,
        private PipelineService $pipelines,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $this->pipelines->ensureDefaultsForUser((int) $user->id);

        $period = (string) $request->query('period', 'month');
        $range = $this->analytics->resolvePeriod(
            $period,
            $request->query('from'),
            $request->query('to'),
            $user->timezone ?? null
        );

        $metrics = $this->analytics->metrics((int) $user->id, $range['from'], $range['to']);

        return view('designer.dashboard', [
            'period' => $period,
            'from' => $range['from']->toDateString(),
            'to' => $range['to']->toDateString(),
            'metrics' => $metrics,
            'charts' => [
                'projects_by_stage' => $this->analytics->projectsByStage((int) $user->id),
                'created_vs_completed' => $this->analytics->createdVsCompleted((int) $user->id, $range['from'], $range['to']),
                'supplies_by_status' => $this->analytics->suppliesByStatus((int) $user->id),
                'deadline_compliance' => $this->analytics->deadlineCompliance((int) $user->id),
            ],
        ]);
    }
}
