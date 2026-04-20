<?php

namespace App\Http\Controllers\Designer;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Supplier;
use App\Models\Supplier_orders;
use App\Models\UserNotification;
use App\Support\PublicFileStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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

        $suppliers = $this->availableSuppliers($userId);

        $orders = Supplier_orders::query()
            ->where('user_id', $userId)
            ->with(['project:id,name', 'supplier:id,name'])
            ->orderByDesc('id')
            ->get();

        $unreadByOrder = $this->chatUnreadMapForDesigner($userId, $orders->pluck('id')->map(fn ($id) => (int) $id)->all());

        $stepsByOrder = Supplier_orders::includedStepsPayloadForMany($orders);

        return view('designer.supplier-orders.index', [
            'projects' => $projects,
            'suppliers' => $suppliers,
            'orders' => $orders->map(fn (Supplier_orders $order) => $this->payload(
                $order,
                $stepsByOrder[(int) $order->id] ?? [],
                $unreadByOrder[(int) $order->id] ?? 0
            ))->values(),
            'selectedProjectId' => $request->query('project_id'),
            'selectedSupplierId' => $request->query('supplier_id'),
            'categoryOptions' => $this->categoryOptions(),
            'roomOptions' => $this->roomOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $userId = (int) $request->user()->id;

        if ($msg = $this->supplierModerationErrorMessage($request, $userId)) {
            return response()->json(['success' => false, 'message' => $msg], 422);
        }

        $order = new Supplier_orders;
        $order->user_id = $userId;

        $this->fillAndSave($request, $order);

        return response()->json([
            'success' => true,
            'message' => __('supplier-orders.created'),
            'order' => $this->payload($order->load(['project:id,name', 'supplier:id,name']), null),
        ]);
    }

    public function update(Request $request, int $orderId)
    {
        $userId = (int) $request->user()->id;

        if ($msg = $this->supplierModerationErrorMessage($request, $userId)) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return redirect()->back()->withErrors(['supplier_id' => $msg])->withInput();
        }

        $order = Supplier_orders::query()
            ->where('user_id', $userId)
            ->findOrFail($orderId);

        $this->fillAndSave($request, $order);

        if (! ($request->expectsJson() || $request->wantsJson())) {
            return redirect()->route('supplier-orders.show', $order->id)->with('status', __('supplier-orders.saved'));
        }

        return response()->json([
            'success' => true,
            'message' => __('supplier-orders.updated'),
            'order' => $this->payload($order->load(['project:id,name', 'supplier:id,name']), null),
        ]);
    }

    public function show(Request $request, int $orderId)
    {
        $order = Supplier_orders::query()
            ->where('user_id', $request->user()->id)
            ->with(['project:id,name', 'supplier:id,name'])
            ->findOrFail($orderId);
        $payload = $this->payload($order, null);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json($payload);
        }

        return view('designer.supplier-orders.show', [
            'order' => $order,
            'orderData' => $this->payload($order, null, $this->chatUnreadMapForDesigner((int) $request->user()->id, [(int) $order->id])[(int) $order->id] ?? 0),
            'projects' => Project::query()->where('user_id', $request->user()->id)->orderBy('name')->get(['id', 'name']),
            'suppliers' => $this->availableSuppliers((int) $request->user()->id),
            'categoryOptions' => $this->categoryOptions(),
            'roomOptions' => $this->roomOptions(),
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

        if (! ($request->expectsJson() || $request->wantsJson())) {
            return redirect()->route('supplier-orders.index')->with('status', __('supplier-orders.deleted'));
        }

        return response()->json([
            'success' => true,
            'message' => __('supplier-orders.deleted'),
        ]);
    }

    public function deleteFile(Request $request, int $orderId, int $fileIndex)
    {
        $order = Supplier_orders::query()
            ->where('user_id', $request->user()->id)
            ->with(['project:id,name', 'supplier:id,name'])
            ->findOrFail($orderId);

        $files = is_array($order->files) ? array_values($order->files) : [];
        if ($files === [] || $fileIndex < 0 || $fileIndex >= count($files)) {
            return response()->json([
                'success' => false,
                'message' => __('supplier-orders.error'),
            ], 422);
        }

        $filePath = $files[$fileIndex];
        if (is_string($filePath) && $filePath !== '') {
            Storage::disk('public')->delete($filePath);
        }

        array_splice($files, $fileIndex, 1);
        $order->files = array_values($files);
        $order->save();

        return response()->json([
            'success' => true,
            'order' => $this->payload($order->fresh(['project:id,name', 'supplier:id,name']), null),
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
            'order' => $this->payload($order->load(['project:id,name', 'supplier:id,name']), null),
        ]);
    }

    private function supplierModerationErrorMessage(Request $request, int $userId): ?string
    {
        $supplierId = (int) $request->input('supplier_id');
        if ($supplierId < 1) {
            return null;
        }

        $supplier = Supplier::query()
            ->where(function ($q) use ($userId) {
                $q->where('created_by_user_id', $userId)
                    ->orWhere(function ($legacy) use ($userId) {
                        $legacy->whereNull('created_by_user_id')
                            ->where('user_id', $userId);
                    });
            })
            ->whereKey($supplierId)
            ->first(['id', 'moderation_status']);

        if (! $supplier) {
            return null;
        }

        $status = (string) ($supplier->moderation_status ?? '');

        if ($status === 'pending') {
            return __('supplier-orders.supplier_moderation_pending');
        }

        if ($status === 'rejected') {
            return __('supplier-orders.supplier_moderation_rejected');
        }

        return null;
    }

    private function fillAndSave(Request $request, Supplier_orders $order): void
    {
        $userId = (int) $request->user()->id;

        if ($request->has('included_step_ids')) {
            $raw = $request->input('included_step_ids');
            if (is_array($raw)) {
                $filtered = array_values(array_filter($raw, function ($v) {
                    if ($v === null || $v === '') {
                        return false;
                    }

                    return is_numeric($v);
                }));
                $request->merge(['included_step_ids' => $filtered]);
            }
        }

        $data = $request->validate([
            'project_id' => ['required', Rule::exists('projects', 'id')->where(fn ($q) => $q->where('user_id', $userId))],
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')],
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'send_to_supplier' => ['nullable', 'boolean'],
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
            'included_step_ids' => ['sometimes', 'nullable', 'array'],
            'included_step_ids.*' => ['nullable', 'integer', 'min:1'],
        ]);

        $projectId = (int) $data['project_id'];
        $stepIds = array_key_exists('included_step_ids', $data)
            ? Supplier_orders::normalizeStepIds($data['included_step_ids'] ?? [])
            : Supplier_orders::normalizeStepIds($order->included_step_ids);

        if ($stepIds !== [] && Supplier_orders::countStepsInProject($projectId, $stepIds) !== count($stepIds)) {
            throw ValidationException::withMessages([
                'included_step_ids' => [__('supplier-orders.included_steps_invalid')],
            ]);
        }

        $links = array_values(array_filter(array_map(fn ($v) => trim((string) $v), (array) ($data['links'] ?? []))));
        $existingFiles = array_values(array_filter(array_map(fn ($v) => trim((string) $v), (array) ($data['existing_files'] ?? []))));
        $supplierId = (int) $data['supplier_id'];

        $supplierAllowed = Supplier::query()
            ->whereKey($supplierId)
            ->where(function ($q) use ($userId) {
                $q->where('created_by_user_id', $userId)
                    ->orWhere(function ($legacy) use ($userId) {
                        $legacy->whereNull('created_by_user_id')
                            ->where('user_id', $userId);
                    })
                    ->orWhere(function ($q2) {
                        $q2->where('profile_status', 'active')
                            ->where('moderation_status', 'approved');
                    });
            })
            ->exists();
        if (! $supplierAllowed) {
            abort(422, __('supplier-orders.supplier_not_linked'));
        }

        $uploadedFiles = [];
        foreach ($request->file('files', []) as $file) {
            if ($file) {
                $uploadedFiles[] = PublicFileStorage::store($file, 'supplier-orders');
            }
        }

        $oldFiles = (array) ($order->files ?? []);
        $newFiles = array_values(array_unique(array_merge($existingFiles, $uploadedFiles)));
        foreach ($oldFiles as $oldFile) {
            if (is_string($oldFile) && $oldFile !== '' && ! in_array($oldFile, $newFiles, true)) {
                Storage::disk('public')->delete($oldFile);
            }
        }

        $order->project_id = $projectId;
        if (array_key_exists('included_step_ids', $data) || ! $order->exists) {
            $order->included_step_ids = $stepIds;
        }
        $order->supplier_id = $supplierId;
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

        if ($request->boolean('send_to_supplier')) {
            $order->status = 'order_sent';
            $order->is_sent_to_supplier = true;
        } else {
            $order->is_sent_to_supplier = false;
        }

        $order->save();

        if ($request->boolean('send_to_supplier')) {
            $supplier = Supplier::query()->find($supplierId);
            if ($supplier && (int) ($supplier->user_id ?? 0) > 0) {
                UserNotification::query()->create([
                    'user_id' => (int) $supplier->user_id,
                    'title' => __('notifications.new_order_title'),
                    'comment' => __('notifications.new_order_comment', ['order' => (string) $order->id]),
                    'is_read' => false,
                    'related_supplier_id' => (int) $supplier->id,
                    'action_key' => 'supplier_order',
                ]);
            }
        }
    }

    private function availableSuppliers(int $designerId)
    {
        return Supplier::query()
            ->where(function ($q) use ($designerId) {
                $q->where('created_by_user_id', $designerId)
                    ->orWhere(function ($legacy) use ($designerId) {
                        $legacy->whereNull('created_by_user_id')
                            ->where('user_id', $designerId);
                    })
                    ->orWhere(function ($q2) {
                        $q2->where('profile_status', 'active')
                            ->where('moderation_status', 'approved');
                    });
            })
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function categoryOptions(): array
    {
        $all = trans('categories');
        if (! is_array($all) || $all === []) {
            return [];
        }

        return $all;
    }

    private function roomOptions(): array
    {
        $all = trans('type_room');
        if (! is_array($all) || $all === []) {
            return [];
        }

        return $all;
    }

    /**
     * @param  list<array<string, mixed>>|null  $includedSteps  из includedStepsPayloadForMany() или null = один запрос
     */
    private function payload(Supplier_orders $order, ?array $includedSteps = null, int $unreadChatCount = 0): array
    {
        $status = (string) $order->status;
        $includedSteps ??= $order->includedStepsPayload();

        return [
            'id' => (int) $order->id,
            'number' => (string) $order->id,
            'created_date' => optional($order->created_at)->format('Y-m-d'),
            'project_id' => (int) $order->project_id,
            'included_step_ids' => Supplier_orders::normalizeStepIds($order->included_step_ids),
            'included_steps' => $includedSteps,
            'project_name' => (string) ($order->project?->name ?? '-'),
            'supplier_id' => (int) $order->supplier_id,
            'supplier_name' => (string) ($order->supplier?->name ?? '-'),
            'status' => $status,
            'is_sent_to_supplier' => (bool) $order->is_sent_to_supplier,
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
                ->map(fn ($f) => is_string($f) ? asset('storage/'.ltrim($f, '/')) : null)
                ->filter()
                ->values(),
            'file_items' => collect(is_array($order->files) ? $order->files : [])
                ->map(function ($f) {
                    if (! is_string($f) || trim($f) === '') {
                        return null;
                    }

                    return [
                        'path' => $f,
                        'name' => basename($f),
                        'url' => asset('storage/'.ltrim($f, '/')),
                    ];
                })
                ->filter()
                ->values(),
            'product_service' => (string) ($order->comment ?? ''),
            'comment' => (string) ($order->comment ?? ''),
            'unread_chat_count' => max(0, $unreadChatCount),
        ];
    }

    /**
     * @param  list<int>  $orderIds
     * @return array<int, int>
     */
    private function chatUnreadMapForDesigner(int $designerUserId, array $orderIds): array
    {
        if (
            $orderIds === []
            || ! Schema::hasTable('supplier_order_messages')
            || ! Schema::hasColumn('supplier_order_messages', 'read_by_designer_at')
        ) {
            return [];
        }

        $rows = DB::table('supplier_order_messages as m')
            ->whereIn('m.supplier_order_id', $orderIds)
            ->where('m.sender_user_id', '!=', $designerUserId)
            ->whereNull('m.read_by_designer_at')
            ->select('m.supplier_order_id', DB::raw('COUNT(*) as unread_count'))
            ->groupBy('m.supplier_order_id')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->supplier_order_id] = (int) $row->unread_count;
        }

        return $map;
    }
}
