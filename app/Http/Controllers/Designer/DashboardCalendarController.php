<?php

namespace App\Http\Controllers\Designer;

use App\Http\Controllers\Controller;
use App\Models\DesignerTask;
use App\Models\ProjectStages;
use App\Models\ProjectStageStep;
use App\Models\Supplier_orders;
use App\Support\WorkspaceAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardCalendarController extends Controller
{
    public function events(Request $request)
    {
        $user = $request->user();
        $userId = (int) $user->id;

        $startRaw = (string) $request->query('start', '');
        $endRaw = (string) $request->query('end', '');

        $start = $startRaw !== '' ? Carbon::parse($startRaw)->startOfDay() : Carbon::now()->startOfMonth();
        $end = $endRaw !== '' ? Carbon::parse($endRaw)->endOfDay() : Carbon::now()->endOfMonth();

        $startDate = $start->toDateString();
        $endDate = $end->toDateString();

        $events = [];

        // 1) Checklist steps
        $steps = WorkspaceAccess::scopeChecklistSteps(
            ProjectStageStep::query()
                ->whereNotNull('deadline')
                ->whereBetween('deadline', [$startDate, $endDate]),
            $user
        )
            ->with([
                'stage:id,project_id,stage_type,name,deadline,responsible_id,created_by',
                'stage.project:id,name,user_id,team_id',
            ])
            ->get();

        foreach ($steps as $step) {
            $stage = $step->stage;
            $project = $stage?->project;
            if (! $stage || ! $project) {
                continue;
            }

            $done = (string) ($step->result_status ?? 'pending') === 'done';
            $deadline = $step->deadline ? (string) $step->deadline : null;

            $events[] = [
                'id' => "checklist_step:{$step->id}",
                'source_type' => 'checklist_step',
                'source_id' => (int) $step->id,
                'event_type' => 'checklist_step',
                'date' => $deadline,
                'time' => '10:00',
                'done' => $done,
                'title' => (string) $step->title,
                'subtitle' => (string) $project->name,
                'status' => $done ? 'done' : 'planned',
                'result_status' => (string) ($step->result_status ?? 'pending'),
                'result_comment' => $step->result_comment,
                'project_id' => (int) $project->id,
                'project_name' => (string) $project->name,
                'project_stage_id' => (int) $stage->id,
                'supplier_name' => null,
                'amount' => null,
                'url_show' => route('tasks.index', array_filter([
                    'project' => (int) $project->id,
                    'checklist' => (int) $stage->id,
                    'item' => (int) $step->id,
                    'date' => $deadline,
                ], fn ($v) => $v !== null && $v !== '')),
                'event_meta' => [
                    'project_stage_type' => (string) $stage->stage_type,
                    'project_stage_id' => (int) $stage->id,
                ],
            ];
        }

        // 1b) Checklist stages with stage-level deadline (even if steps have no deadline)
        $stages = ProjectStages::query()
            ->whereNotNull('deadline')
            ->whereBetween('deadline', [$startDate, $endDate])
            ->whereHas('project', function ($q) use ($user) {
                WorkspaceAccess::scopeProjects($q, $user);
            })
            ->with([
                'project:id,name,user_id,team_id',
                'responsible:id,name',
                'steps:id,project_stage_id,result_status,deadline,responsible_id',
            ])
            ->get();

        if (WorkspaceAccess::isCorporate($user) && ! WorkspaceAccess::canSeeAllTeamTasks($user)) {
            $uid = (int) $user->id;
            $stages = $stages->filter(function (ProjectStages $stage) use ($uid) {
                return (int) $stage->responsible_id === $uid
                    || (int) ($stage->created_by ?? 0) === $uid
                    || $stage->steps->contains(fn ($s) => (int) $s->responsible_id === $uid);
            });
        }

        foreach ($stages as $stage) {
            $project = $stage->project;
            if (! $project) {
                continue;
            }
            // Avoid doubling when a step in this stage already appears in the date range.
            if ($stage->steps->contains(function ($s) use ($startDate, $endDate) {
                if (empty($s->deadline)) {
                    return false;
                }
                $d = Carbon::parse($s->deadline)->toDateString();

                return $d >= $startDate && $d <= $endDate;
            })) {
                continue;
            }
            $steps = $stage->steps;
            $total = $steps->count();
            $doneCount = $steps->where('result_status', 'done')->count();
            $done = $total > 0 && $doneCount === $total;
            $deadline = $stage->deadline ? Carbon::parse($stage->deadline)->toDateString() : null;
            $type = (string) $stage->stage_type;
            $labelKey = 'projects.stage_'.$type;
            $stageLabel = $type !== '' ? (string) __($labelKey) : '';
            if ($stageLabel === $labelKey) {
                $stageLabel = $type;
            }
            $customName = is_string($stage->name) ? trim($stage->name) : '';
            $title = $customName !== '' ? $customName : $stageLabel;

            $events[] = [
                'id' => "checklist_stage:{$stage->id}",
                'source_type' => 'checklist_step',
                'source_id' => null,
                'event_type' => 'checklist_step',
                'date' => $deadline,
                'time' => '09:00',
                'done' => $done,
                'title' => $title,
                'subtitle' => (string) $project->name,
                'status' => $done ? 'done' : 'planned',
                'project_id' => (int) $project->id,
                'project_name' => (string) $project->name,
                'project_stage_id' => (int) $stage->id,
                'supplier_name' => null,
                'amount' => null,
                'url_show' => route('tasks.index', array_filter([
                    'project' => (int) $project->id,
                    'checklist' => (int) $stage->id,
                    'date' => $deadline,
                    'view' => 'calendar',
                ], fn ($v) => $v !== null && $v !== '')),
                'event_meta' => [
                    'project_stage_type' => $type,
                    'project_stage_id' => (int) $stage->id,
                ],
            ];
        }

        // 2) Supplier orders
        $ordersBase = Supplier_orders::query()
            ->where('user_id', $userId)
            ->with([
                'project:id,name',
                'supplier:id,name',
            ]);

        $statusOrder = fn (Supplier_orders $o): string => (string) $o->status;

        // Planned delivery (date_planned)
        $orders = (clone $ordersBase)
            ->whereBetween('date_planned', [$startDate, $endDate])
            ->get();

        foreach ($orders as $order) {
            $project = $order->project;
            $supplier = $order->supplier;
            if (! $project || ! $supplier) {
                continue;
            }

            $done = $statusOrder($order) === 'delivery_completed';

            $events[] = [
                'id' => "supplier_order:{$order->id}:delivery_planned",
                'source_type' => 'supplier_order',
                'source_id' => (int) $order->id,
                'event_type' => 'delivery_planned',
                'date' => $order->date_planned?->toDateString(),
                'time' => '16:00',
                'done' => $done,
                'title' => "{$supplier->name} — план поставки",
                'subtitle' => $project->name,
                'status' => $done ? 'done' : 'planned',
                'project_id' => (int) $project->id,
                'project_name' => (string) $project->name,
                'supplier_name' => (string) $supplier->name,
                'amount' => (int) ($order->summa ?? 0),
                'url_show' => route('supplier-orders.show', $order->id),
                'event_meta' => [
                    'order_status' => (string) $order->status,
                ],
            ];
        }

        // Actual delivery (date_actual)
        $orders = (clone $ordersBase)
            ->whereNotNull('date_actual')
            ->whereBetween('date_actual', [$startDate, $endDate])
            ->get();

        foreach ($orders as $order) {
            $project = $order->project;
            $supplier = $order->supplier;
            if (! $project || ! $supplier) {
                continue;
            }

            $done = $statusOrder($order) === 'delivery_completed';

            $events[] = [
                'id' => "supplier_order:{$order->id}:delivery_actual",
                'source_type' => 'supplier_order',
                'source_id' => (int) $order->id,
                'event_type' => 'delivery_actual',
                'date' => $order->date_actual?->toDateString(),
                'time' => '12:00',
                'done' => $done,
                'title' => "{$supplier->name} — поставка (факт)",
                'subtitle' => $project->name,
                'status' => $done ? 'done' : 'planned',
                'project_id' => (int) $project->id,
                'project_name' => (string) $project->name,
                'supplier_name' => (string) $supplier->name,
                'amount' => (int) ($order->summa ?? 0),
                'url_show' => route('supplier-orders.show', $order->id),
                'event_meta' => [
                    'order_status' => (string) $order->status,
                ],
            ];
        }

        // Prepayment (prepayment_date)
        $orders = (clone $ordersBase)
            ->whereNotNull('prepayment_date')
            ->whereBetween('prepayment_date', [$startDate, $endDate])
            ->get();

        foreach ($orders as $order) {
            $project = $order->project;
            $supplier = $order->supplier;
            if (! $project || ! $supplier) {
                continue;
            }

            $st = $statusOrder($order);
            $done = in_array($st, ['advance_payment', 'full_payment', 'delivery_completed'], true);

            $events[] = [
                'id' => "supplier_order:{$order->id}:prepayment",
                'source_type' => 'supplier_order',
                'source_id' => (int) $order->id,
                'event_type' => 'prepayment',
                'date' => $order->prepayment_date?->toDateString(),
                'time' => '11:00',
                'done' => $done,
                'title' => "{$supplier->name} — аванс",
                'subtitle' => $project->name,
                'status' => $done ? 'done' : 'planned',
                'project_id' => (int) $project->id,
                'project_name' => (string) $project->name,
                'supplier_name' => (string) $supplier->name,
                'amount' => (int) ($order->prepayment_amount ?? 0),
                'url_show' => route('supplier-orders.show', $order->id),
                'event_meta' => [
                    'order_status' => (string) $order->status,
                ],
            ];
        }

        // Balance payment (payment_date)
        $orders = (clone $ordersBase)
            ->whereNotNull('payment_date')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->get();

        foreach ($orders as $order) {
            $project = $order->project;
            $supplier = $order->supplier;
            if (! $project || ! $supplier) {
                continue;
            }

            $st = $statusOrder($order);
            $done = in_array($st, ['full_payment', 'delivery_completed'], true);

            $events[] = [
                'id' => "supplier_order:{$order->id}:balance_payment",
                'source_type' => 'supplier_order',
                'source_id' => (int) $order->id,
                'event_type' => 'balance_payment',
                'date' => $order->payment_date?->toDateString(),
                'time' => '14:00',
                'done' => $done,
                'title' => "{$supplier->name} — доплата",
                'subtitle' => $project->name,
                'status' => $done ? 'done' : 'planned',
                'project_id' => (int) $project->id,
                'project_name' => (string) $project->name,
                'supplier_name' => (string) $supplier->name,
                'amount' => (int) ($order->payment_amount ?? 0),
                'url_show' => route('supplier-orders.show', $order->id).'?source=dashboard&focus=payment',
                'event_meta' => [
                    'order_status' => (string) $order->status,
                ],
            ];
        }

        // 3) Regular designer tasks
        $designerTasks = WorkspaceAccess::scopeDesignerTasks(
            DesignerTask::query()
                ->whereNotNull('due_at')
                ->whereBetween('due_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()]),
            $user
        )
            ->with(['assignee:id,name', 'project:id,name', 'creator:id,name'])
            ->get();

        foreach ($designerTasks as $task) {
            $due = $task->due_at;
            $status = $task->status?->value ?? (string) $task->status;
            $events[] = [
                'id' => "designer_task:{$task->id}",
                'source_type' => 'designer_task',
                'source_id' => (int) $task->id,
                'event_type' => 'designer_task',
                'date' => $due?->toDateString(),
                'time' => $due?->format('H:i'),
                'done' => $status === 'completed',
                'title' => (string) $task->title,
                'subtitle' => $task->project?->name ?: ($task->assignee?->name ?? ''),
                'status' => $status,
                'status_label' => $task->status?->label(),
                'assignee_name' => $task->assignee?->name,
                'creator_name' => $task->creator?->name,
                'project_id' => $task->project_id ? (int) $task->project_id : null,
                'project_name' => $task->project?->name,
                'supplier_name' => null,
                'amount' => null,
                'is_overdue' => $task->isOverdue(),
                'url_show' => route('tasks.index', ['task' => $task->id, 'view' => 'calendar']),
                'event_meta' => [
                    'task_id' => (int) $task->id,
                ],
            ];
        }

        // Сортировка: дата, затем час (если есть)
        usort($events, function ($a, $b) {
            $da = (string) ($a['date'] ?? '');
            $db = (string) ($b['date'] ?? '');

            if ($da === $db) {
                $ta = (string) ($a['time'] ?? '');
                $tb = (string) ($b['time'] ?? '');

                return strcmp($ta, $tb);
            }

            return strcmp($da, $db);
        });

        return response()->json([
            'events' => $events,
            'start' => $startDate,
            'end' => $endDate,
        ]);
    }
}
