<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Supplier;
use App\Models\Supplier_orders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SupplierOrderController extends Controller
{
    private const STATUSES = [
        'order_created',
        'order_sent',
        'order_confirmed',
        'advance_payment',
        'full_payment',
        'delivery_completed',
    ];

    public function index(Request $request)
    {
        $userId = (int) $request->user()->id;

        $projects = Project::query()
            ->where('user_id', $userId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $suppliers = Supplier::query()
            ->where('user_id', $userId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $orders = Supplier_orders::query()
            ->where('user_id', $userId)
            ->with(['project:id,name', 'supplier:id,name'])
            ->orderByDesc('id')
            ->get();

        return view('supplier-orders.index', [
            'projects' => $projects,
            'suppliers' => $suppliers,
            'orders' => $orders->map(fn (Supplier_orders $order) => $this->payload($order))->values(),
            'selectedProjectId' => $request->query('project_id'),
            'selectedSupplierId' => $request->query('supplier_id'),
        ]);
    }

    public function store(Request $request)
    {
        $order = new Supplier_orders();
        $order->user_id = (int) $request->user()->id;

        $this->fillAndSave($request, $order);

        return response()->json([
            'success' => true,
            'message' => __('supplier-orders.created'),
            'order' => $this->payload($order->load(['project:id,name', 'supplier:id,name'])),
        ]);
    }

    public function update(Request $request, int $orderId)
    {
        $order = Supplier_orders::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($orderId);

        $this->fillAndSave($request, $order);

        return response()->json([
            'success' => true,
            'message' => __('supplier-orders.updated'),
            'order' => $this->payload($order->load(['project:id,name', 'supplier:id,name'])),
        ]);
    }

    public function destroy(Request $request, int $orderId)
    {
        $order = Supplier_orders::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($orderId);

        foreach ((array) ($order->files ?? []) as $filePath) {
            if (is_string($filePath) && $filePath !== '') {
                Storage::disk('public')->delete($filePath);
            }
        }

        $order->delete();

        return response()->json([
            'success' => true,
            'message' => __('supplier-orders.deleted'),
        ]);
    }

    public function updateStatus(Request $request, int $orderId)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(self::STATUSES)],
        ]);

        $order = Supplier_orders::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($orderId);

        $order->status = $data['status'];
        $order->save();

        return response()->json([
            'success' => true,
            'message' => __('supplier-orders.updated'),
            'order' => $this->payload($order->load(['project:id,name', 'supplier:id,name'])),
        ]);
    }

    private function fillAndSave(Request $request, Supplier_orders $order): void
    {
        $userId = (int) $request->user()->id;

        $data = $request->validate([
            'project_id' => ['required', Rule::exists('projects', 'id')->where(fn ($q) => $q->where('user_id', $userId))],
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where(fn ($q) => $q->where('user_id', $userId))],
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'summa' => ['required', 'integer', 'min:0'],
            'category' => ['nullable', 'string', 'max:255'],
            'mark' => ['nullable', 'string', 'max:255'],
            'room' => ['nullable', 'string', 'max:255'],
            'date_planned' => ['required', 'date'],
            'date_actual' => ['nullable', 'date'],
            'prepayment_date' => ['nullable', 'date'],
            'payment_date' => ['nullable', 'date'],
            'prepayment_amount' => ['nullable', 'integer', 'min:0'],
            'payment_amount' => ['nullable', 'integer', 'min:0'],
            'links' => ['nullable', 'array'],
            'links.*' => ['nullable', 'url', 'max:1000'],
            'existing_files' => ['nullable', 'array'],
            'existing_files.*' => ['nullable', 'string', 'max:1000'],
            'files' => ['nullable', 'array'],
            'files.*' => ['nullable', 'file', 'max:10240'],
            'comment' => ['nullable', 'string'],
        ]);

        $links = array_values(array_filter(array_map(fn ($v) => trim((string) $v), (array) ($data['links'] ?? []))));
        $existingFiles = array_values(array_filter(array_map(fn ($v) => trim((string) $v), (array) ($data['existing_files'] ?? []))));

        $uploadedFiles = [];
        foreach ($request->file('files', []) as $file) {
            if ($file) {
                $uploadedFiles[] = $file->store('supplier-orders', 'public');
            }
        }

        $oldFiles = (array) ($order->files ?? []);
        $newFiles = array_values(array_unique(array_merge($existingFiles, $uploadedFiles)));
        foreach ($oldFiles as $oldFile) {
            if (is_string($oldFile) && $oldFile !== '' && ! in_array($oldFile, $newFiles, true)) {
                Storage::disk('public')->delete($oldFile);
            }
        }

        $order->project_id = (int) $data['project_id'];
        $order->supplier_id = (int) $data['supplier_id'];
        $order->status = (string) ($data['status'] ?? 'order_created');
        $order->summa = (int) $data['summa'];
        $order->category = $data['category'] ?? null;
        $order->mark = $data['mark'] ?? null;
        $order->room = $data['room'] ?? null;
        $order->date_planned = $data['date_planned'];
        $order->date_actual = $data['date_actual'] ?? null;
        $order->prepayment_date = $data['prepayment_date'] ?? null;
        $order->payment_date = $data['payment_date'] ?? null;
        $order->prepayment_amount = isset($data['prepayment_amount']) ? (int) $data['prepayment_amount'] : null;
        $order->payment_amount = isset($data['payment_amount']) ? (int) $data['payment_amount'] : null;
        $order->links = $links;
        $order->files = $newFiles;
        $order->comment = $data['comment'] ?? null;
        $order->save();
    }

    private function payload(Supplier_orders $order): array
    {
        $status = (string) $order->status;

        return [
            'id' => (int) $order->id,
            'number' => (string) $order->id,
            'created_date' => optional($order->created_at)->format('Y-m-d'),
            'project_id' => (int) $order->project_id,
            'project_name' => (string) ($order->project?->name ?? '-'),
            'supplier_id' => (int) $order->supplier_id,
            'supplier_name' => (string) ($order->supplier?->name ?? '-'),
            'status' => $status,
            'amount' => (int) $order->summa,
            'summa' => (int) $order->summa,
            'category' => (string) ($order->category ?? ''),
            'mark' => (string) ($order->mark ?? ''),
            'room' => (string) ($order->room ?? ''),
            'planned_date' => optional($order->date_planned)->format('Y-m-d'),
            'date_planned' => optional($order->date_planned)->format('Y-m-d'),
            'actual_date' => optional($order->date_actual)->format('Y-m-d'),
            'date_actual' => optional($order->date_actual)->format('Y-m-d'),
            'prepayment_date' => optional($order->prepayment_date)->format('Y-m-d'),
            'payment_date' => optional($order->payment_date)->format('Y-m-d'),
            'prepayment_amount' => $order->prepayment_amount !== null ? (int) $order->prepayment_amount : null,
            'payment_amount' => $order->payment_amount !== null ? (int) $order->payment_amount : null,
            'links' => is_array($order->links) ? $order->links : [],
            'files' => is_array($order->files) ? $order->files : [],
            'file_urls' => collect(is_array($order->files) ? $order->files : [])
                ->map(fn ($f) => is_string($f) ? asset('storage/' . ltrim($f, '/')) : null)
                ->filter()
                ->values(),
            'product_service' => (string) ($order->comment ?? ''),
            'comment' => (string) ($order->comment ?? ''),
        ];
    }
}
