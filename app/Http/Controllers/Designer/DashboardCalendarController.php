<?php

namespace App\Http\Controllers\Designer;

use App\Http\Controllers\Controller;
use App\Models\ProjectStageStep;
use App\Models\Supplier_orders;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardCalendarController extends Controller
{
    public function events(Request $request)
    {
        $userId = (int) $request->user()->id;

        $startRaw = (string) $request->query('start', '');
        $endRaw = (string) $request->query('end', '');

        $start = $startRaw !== '' ? Carbon::parse($startRaw)->startOfDay() : Carbon::now()->startOfMonth();
        $end = $endRaw !== '' ? Carbon::parse($endRaw)->endOfDay() : Carbon::now()->endOfMonth();

        $startDate = $start->toDateString();
        $endDate = $end->toDateString();

        $events = [];

        // 1) Checklist steps
        $steps = ProjectStageStep::query()
            ->whereNotNull('deadline')
            ->whereBetween('deadline', [$startDate, $endDate])
            ->whereHas('stage.project', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->with([
                'stage:id,project_id,stage_type,responsible_id',
                'stage.project:id,name,user_id',
            ])
            ->get();

        foreach ($steps as $step) {
            $stage = $step->stage;
            $project = $stage?->project;
            if (! $stage || ! $project) {
                continue;
            }

            $done = (string) ($step->result_status ?? 'pending') === 'done';

            $events[] = [
                'id' => "checklist_step:{$step->id}",
                'source_type' => 'checklist_step',
                'source_id' => (int) $step->id,
                'event_type' => 'checklist_step',
                'date' => $step->deadline ? (string) $step->deadline : null,
                'time' => '10:00',
                'done' => $done,
                'title' => (string) $step->title,
                'subtitle' => (string) $project->name,
                'status' => $done ? 'done' : 'planned',
                'result_status' => (string) ($step->result_status ?? 'pending'),
                'result_comment' => $step->result_comment,
                'project_id' => (int) $project->id,
                'project_name' => (string) $project->name,
                'supplier_name' => null,
                'amount' => null,
                'url_show' => route('checklist-steps.show', $step->id),
                'event_meta' => [
                    'project_stage_type' => (string) $stage->stage_type,
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
                'url_show' => route('supplier-orders.show', $order->id),
                'event_meta' => [
                    'order_status' => (string) $order->status,
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
