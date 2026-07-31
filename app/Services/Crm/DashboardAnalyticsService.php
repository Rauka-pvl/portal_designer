<?php

namespace App\Services\Crm;

use App\Enums\ProjectStatus;
use App\Enums\SupplyStatus;
use App\Models\Project;
use App\Models\ProjectStageStep;
use App\Models\Supplier_orders;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class DashboardAnalyticsService
{
    /**
     * @return array{from: Carbon, to: Carbon}
     */
    public function resolvePeriod(string $period, ?string $from = null, ?string $to = null, ?string $timezone = null): array
    {
        $tz = $timezone ?: config('app.timezone', 'UTC');
        $now = Carbon::now($tz);

        return match ($period) {
            'week' => ['from' => $now->copy()->startOfWeek(), 'to' => $now->copy()->endOfWeek()],
            'quarter' => ['from' => $now->copy()->firstOfQuarter(), 'to' => $now->copy()->lastOfQuarter()],
            'year' => ['from' => $now->copy()->startOfYear(), 'to' => $now->copy()->endOfYear()],
            'custom' => [
                'from' => $from ? Carbon::parse($from, $tz)->startOfDay() : $now->copy()->startOfMonth(),
                'to' => $to ? Carbon::parse($to, $tz)->endOfDay() : $now->copy()->endOfDay(),
            ],
            default => ['from' => $now->copy()->startOfMonth(), 'to' => $now->copy()->endOfMonth()],
        };
    }

    public function metrics(int $userId, Carbon $from, Carbon $to): array
    {
        $now = Carbon::now();

        $activeProjects = Project::query()
            ->where('user_id', $userId)
            ->where(function ($q) {
                $q->whereNull('actual_end_date')
                    ->orWhere('status', '!=', ProjectStatus::InWork->value);
            })
            ->count();

        // Treat "in_work" with past planned_end as overdue active work; also any with past planned_end and no actual_end
        $overdueProjects = Project::query()
            ->where('user_id', $userId)
            ->whereNull('actual_end_date')
            ->whereNotNull('planned_end_date')
            ->whereDate('planned_end_date', '<', $now->toDateString())
            ->count();

        $deadlines7 = Project::query()
            ->where('user_id', $userId)
            ->whereNull('actual_end_date')
            ->whereNotNull('planned_end_date')
            ->whereBetween('planned_end_date', [$now->toDateString(), $now->copy()->addDays(7)->toDateString()])
            ->count();

        $overdueChecklists = ProjectStageStep::query()
            ->where('result_status', '!=', 'done')
            ->whereNotNull('deadline')
            ->whereDate('deadline', '<', $now->toDateString())
            ->whereHas('stage.project', fn ($q) => $q->where('user_id', $userId))
            ->count();

        $delayedSupplies = Supplier_orders::query()
            ->where('user_id', $userId)
            ->where('status', '!=', SupplyStatus::DeliveryCompleted->value)
            ->whereNotNull('date_planned')
            ->whereDate('date_planned', '<', $now->toDateString())
            ->count();

        $completedInPeriod = Project::query()
            ->where('user_id', $userId)
            ->whereNotNull('actual_end_date')
            ->whereBetween('actual_end_date', [$from->toDateString(), $to->toDateString()])
            ->count();

        return [
            'active_projects' => $activeProjects,
            'overdue_projects' => $overdueProjects,
            'deadlines_7_days' => $deadlines7,
            'overdue_checklists' => $overdueChecklists,
            'delayed_supplies' => $delayedSupplies,
            'completed_projects' => $completedInPeriod,
        ];
    }

    public function projectsByStage(int $userId): array
    {
        $rows = Project::query()
            ->where('user_id', $userId)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $labels = [];
        $values = [];
        $colors = [];

        foreach (ProjectStatus::funnelOrder() as $status) {
            $labels[] = $status->label();
            $values[] = (int) ($rows[$status->value] ?? 0);
            $colors[] = $status->defaultColor();
        }

        return compact('labels', 'values', 'colors');
    }

    public function createdVsCompleted(int $userId, Carbon $from, Carbon $to): array
    {
        $period = CarbonPeriod::create($from->copy()->startOfDay(), '1 day', $to->copy()->endOfDay());
        $labels = [];
        $created = [];
        $completed = [];

        $createdMap = Project::query()
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$from, $to])
            ->select(DB::raw('DATE(created_at) as d'), DB::raw('count(*) as total'))
            ->groupBy('d')
            ->pluck('total', 'd');

        $completedMap = Project::query()
            ->where('user_id', $userId)
            ->whereNotNull('actual_end_date')
            ->whereBetween('actual_end_date', [$from->toDateString(), $to->toDateString()])
            ->select(DB::raw('DATE(actual_end_date) as d'), DB::raw('count(*) as total'))
            ->groupBy('d')
            ->pluck('total', 'd');

        // For long ranges, bucket by week/month
        $days = $from->diffInDays($to);
        if ($days > 90) {
            return $this->createdVsCompletedMonthly($userId, $from, $to);
        }

        foreach ($period as $date) {
            $key = $date->toDateString();
            $labels[] = $date->format('d.m');
            $created[] = (int) ($createdMap[$key] ?? 0);
            $completed[] = (int) ($completedMap[$key] ?? 0);
        }

        return compact('labels', 'created', 'completed');
    }

    private function createdVsCompletedMonthly(int $userId, Carbon $from, Carbon $to): array
    {
        $labels = [];
        $created = [];
        $completed = [];
        $cursor = $from->copy()->startOfMonth();

        while ($cursor <= $to) {
            $mFrom = $cursor->copy()->startOfMonth();
            $mTo = $cursor->copy()->endOfMonth();
            $labels[] = $cursor->translatedFormat('M Y');
            $created[] = Project::query()
                ->where('user_id', $userId)
                ->whereBetween('created_at', [$mFrom, $mTo])
                ->count();
            $completed[] = Project::query()
                ->where('user_id', $userId)
                ->whereNotNull('actual_end_date')
                ->whereBetween('actual_end_date', [$mFrom->toDateString(), $mTo->toDateString()])
                ->count();
            $cursor->addMonth();
        }

        return compact('labels', 'created', 'completed');
    }

    public function suppliesByStatus(int $userId): array
    {
        $rows = Supplier_orders::query()
            ->where('user_id', $userId)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $labels = [];
        $values = [];
        $colors = [];

        foreach (SupplyStatus::cases() as $status) {
            $count = (int) ($rows[$status->value] ?? 0);
            if ($count === 0 && $status === SupplyStatus::Draft) {
                // still include if any drafts exist elsewhere; skip empty draft to reduce noise only when zero
            }
            $labels[] = $status->label();
            $values[] = $count;
            $colors[] = $status->defaultColor();
        }

        return compact('labels', 'values', 'colors');
    }

    public function deadlineCompliance(int $userId): array
    {
        $projects = Project::query()
            ->where('user_id', $userId)
            ->whereNotNull('planned_end_date')
            ->get(['id', 'planned_end_date', 'actual_end_date']);

        $onTime = 0;
        $delayed = 0;
        $overdue = 0;
        $today = Carbon::today();

        foreach ($projects as $project) {
            $planned = Carbon::parse($project->planned_end_date)->startOfDay();
            if ($project->actual_end_date) {
                $actual = Carbon::parse($project->actual_end_date)->startOfDay();
                if ($actual->lte($planned)) {
                    $onTime++;
                } else {
                    $delayed++;
                }
            } elseif ($planned->lt($today)) {
                $overdue++;
            }
        }

        return [
            'labels' => [
                __('dashboard.crm_on_time'),
                __('dashboard.crm_delayed'),
                __('dashboard.crm_overdue'),
            ],
            'values' => [$onTime, $delayed, $overdue],
            'colors' => ['#22c55e', '#eab308', '#ef4444'],
        ];
    }

    /**
     * API-oriented dashboard data while retaining the web dashboard's calculations.
     */
    public function forApi(User $user, string $period, ?string $from, ?string $to, ?string $timezone): array
    {
        $range = $this->resolvePeriod($period, $from, $to, $timezone);
        $metrics = $this->metrics((int) $user->id, $range['from'], $range['to']);
        $createdCompleted = $this->createdVsCompleted((int) $user->id, $range['from'], $range['to']);
        $deadlines = $this->deadlineCompliance((int) $user->id);

        return [
            'period' => [
                'type' => $period,
                'date_from' => $range['from']->toDateString(),
                'date_to' => $range['to']->toDateString(),
            ],
            'metrics' => [
                'active_projects' => $metrics['active_projects'],
                'overdue_projects' => $metrics['overdue_projects'],
                'upcoming_deadlines' => $metrics['deadlines_7_days'],
                'overdue_checklists' => $metrics['overdue_checklists'],
                'delayed_supplies' => $metrics['delayed_supplies'],
                'completed_projects' => $metrics['completed_projects'],
            ],
            'charts' => [
                'projects_by_stage' => collect(ProjectStatus::funnelOrder())
                    ->map(fn (ProjectStatus $status) => [
                        'stage' => $status->value,
                        'count' => (int) (Project::query()->where('user_id', $user->id)->where('status', $status->value)->count()),
                    ])->values()->all(),
                'created_and_completed_projects' => collect($createdCompleted['labels'])
                    ->map(fn (string $label, int $index) => [
                        'period' => $label,
                        'created' => (int) ($createdCompleted['created'][$index] ?? 0),
                        'completed' => (int) ($createdCompleted['completed'][$index] ?? 0),
                    ])->values()->all(),
                'supplies_by_status' => collect(SupplyStatus::cases())
                    ->map(fn (SupplyStatus $status) => [
                        'status' => $status->value,
                        'count' => (int) (Supplier_orders::query()->where('user_id', $user->id)->where('status', $status->value)->count()),
                    ])->values()->all(),
                'deadline_performance' => [
                    'on_time' => (int) ($deadlines['values'][0] ?? 0),
                    'late' => (int) ($deadlines['values'][1] ?? 0),
                    'overdue' => (int) ($deadlines['values'][2] ?? 0),
                ],
                'project_completion_dynamics' => collect($createdCompleted['labels'])
                    ->map(fn (string $label, int $index) => [
                        'period' => $label,
                        'completed' => (int) ($createdCompleted['completed'][$index] ?? 0),
                    ])->values()->all(),
            ],
        ];
    }
}
