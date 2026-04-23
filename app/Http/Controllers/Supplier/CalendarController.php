<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\Supplier_orders;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(Request $request): View
    {
        $supplier = Supplier::query()
            ->where('user_id', (int) $request->user()->id)
            ->first();

        $profileStatus = (string) ($supplier?->profile_status ?? 'draft');
        $moderationStatus = (string) ($supplier?->moderation_status ?? 'draft');
        $shouldShowInitialProfileModal = $supplier !== null
            && (int) ($supplier->created_by_user_id ?? 0) === 0
            && $profileStatus === 'draft'
            && in_array($moderationStatus, ['', 'draft'], true);

        return view('supplier.calendar', [
            'supplier' => $supplier,
            'stats' => $this->statsForSupplier($supplier),
            'showInitialProfileModal' => $shouldShowInitialProfileModal,
        ]);
    }

    public function events(Request $request)
    {
        $supplier = Supplier::query()
            ->where('user_id', (int) $request->user()->id)
            ->first();

        if (! $supplier) {
            return response()->json([
                'events' => [],
                'start' => null,
                'end' => null,
            ]);
        }

        $startRaw = (string) $request->query('start', '');
        $endRaw = (string) $request->query('end', '');

        $start = $startRaw !== '' ? Carbon::parse($startRaw)->startOfDay() : Carbon::now()->startOfMonth();
        $end = $endRaw !== '' ? Carbon::parse($endRaw)->endOfDay() : Carbon::now()->endOfMonth();

        $startDate = $start->toDateString();
        $endDate = $end->toDateString();

        $events = [];

        $ordersBase = Supplier_orders::query()
            ->where('supplier_id', (int) $supplier->id)
            ->where('is_sent_to_supplier', true)
            ->with([
                'project:id,name',
                'designer:id,name,email',
                'supplier:id,name',
            ]);

        $statusOrder = fn (Supplier_orders $o): string => (string) $o->status;

        $orders = (clone $ordersBase)
            ->whereBetween('date_planned', [$startDate, $endDate])
            ->get();

        foreach ($orders as $order) {
            $project = $order->project;
            if (! $project) {
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
                'title' => __('supplier-portal.calendar_delivery_planned'),
                'subtitle' => (string) $project->name,
                'status' => $done ? 'done' : 'planned',
                'project_id' => (int) $project->id,
                'project_name' => (string) $project->name,
                'designer_name' => (string) ($order->designer?->name ?? ''),
                'amount' => (int) ($order->summa ?? 0),
                'url_show' => route('supplier.orders'),
                'event_meta' => [
                    'order_status' => (string) $order->status,
                ],
            ];
        }

        $orders = (clone $ordersBase)
            ->whereNotNull('date_actual')
            ->whereBetween('date_actual', [$startDate, $endDate])
            ->get();

        foreach ($orders as $order) {
            $project = $order->project;
            if (! $project) {
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
                'title' => __('supplier-portal.calendar_delivery_actual'),
                'subtitle' => (string) $project->name,
                'status' => $done ? 'done' : 'planned',
                'project_id' => (int) $project->id,
                'project_name' => (string) $project->name,
                'designer_name' => (string) ($order->designer?->name ?? ''),
                'amount' => (int) ($order->summa ?? 0),
                'url_show' => route('supplier.orders'),
                'event_meta' => [
                    'order_status' => (string) $order->status,
                ],
            ];
        }

        $orders = (clone $ordersBase)
            ->whereNotNull('prepayment_date')
            ->whereBetween('prepayment_date', [$startDate, $endDate])
            ->get();

        foreach ($orders as $order) {
            $project = $order->project;
            if (! $project) {
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
                'title' => __('supplier-portal.calendar_prepayment'),
                'subtitle' => (string) $project->name,
                'status' => $done ? 'done' : 'planned',
                'project_id' => (int) $project->id,
                'project_name' => (string) $project->name,
                'designer_name' => (string) ($order->designer?->name ?? ''),
                'amount' => (int) ($order->prepayment_amount ?? 0),
                'url_show' => route('supplier.orders'),
                'event_meta' => [
                    'order_status' => (string) $order->status,
                ],
            ];
        }

        $orders = (clone $ordersBase)
            ->whereNotNull('payment_date')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->get();

        foreach ($orders as $order) {
            $project = $order->project;
            if (! $project) {
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
                'title' => __('supplier-portal.calendar_balance_payment'),
                'subtitle' => (string) $project->name,
                'status' => $done ? 'done' : 'planned',
                'project_id' => (int) $project->id,
                'project_name' => (string) $project->name,
                'designer_name' => (string) ($order->designer?->name ?? ''),
                'amount' => (int) ($order->payment_amount ?? 0),
                'url_show' => route('supplier.orders'),
                'event_meta' => [
                    'order_status' => (string) $order->status,
                ],
            ];
        }

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

    /**
     * @return array<string, int>
     */
    private function statsForSupplier(?Supplier $supplier): array
    {
        if (! $supplier) {
            return [
                'orders_in_work' => 0,
                'tasks_today' => 0,
                'planned_deliveries' => 0,
                'overdue_deliveries' => 0,
            ];
        }

        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth()->toDateString();
        $monthEnd = $today->copy()->endOfMonth()->toDateString();
        $todayDate = $today->toDateString();

        $ordersBase = Supplier_orders::query()
            ->where('supplier_id', (int) $supplier->id)
            ->where('is_sent_to_supplier', true);

        $ordersInWork = (clone $ordersBase)
            ->where('status', '!=', 'delivery_completed')
            ->count();

        $plannedDeliveries = (clone $ordersBase)
            ->whereBetween('date_planned', [$monthStart, $monthEnd])
            ->count();

        $overdueDeliveries = (clone $ordersBase)
            ->whereDate('date_planned', '<', $todayDate)
            ->where('status', '!=', 'delivery_completed')
            ->count();

        $tasksToday =
            (clone $ordersBase)->whereDate('date_planned', $todayDate)->count()
            + (clone $ordersBase)->whereDate('date_actual', $todayDate)->count()
            + (clone $ordersBase)->whereDate('prepayment_date', $todayDate)->count()
            + (clone $ordersBase)->whereDate('payment_date', $todayDate)->count();

        return [
            'orders_in_work' => (int) $ordersInWork,
            'tasks_today' => (int) $tasksToday,
            'planned_deliveries' => (int) $plannedDeliveries,
            'overdue_deliveries' => (int) $overdueDeliveries,
        ];
    }
}
