<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\Supplier_orders;
use App\Support\PublicFileStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupplierPortalController extends Controller
{
    private const ORDER_STATUSES = [
        'order_created',
        'order_sent',
        'order_confirmed',
        'advance_payment',
        'full_payment',
        'delivery_completed',
    ];

    public function orders(Request $request): View
    {
        $supplier = $this->resolveSupplierForUser((int) $request->user()->id);

        $orders = collect();
        if ($supplier) {
            $orderModels = Supplier_orders::query()
                ->where('supplier_id', $supplier->id)
                ->where('is_sent_to_supplier', true)
                ->with([
                    'project:id,name',
                    'designer:id,name,email',
                    'supplier:id,name',
                ])
                ->orderByDesc('id')
                ->get();

            $unreadByOrder = $this->chatUnreadMapForSupplier(
                (int) $request->user()->id,
                $orderModels->pluck('id')->map(fn ($id) => (int) $id)->all()
            );
            $stepsByOrder = Supplier_orders::includedStepsPayloadForMany($orderModels);

            $orders = $orderModels->map(fn (Supplier_orders $order) => $this->orderPayload(
                $order,
                $stepsByOrder[(int) $order->id] ?? [],
                $unreadByOrder[(int) $order->id] ?? 0
            ));
        }

        $projects = $orders
            ->map(fn (array $o) => ['id' => $o['project_id'], 'name' => $o['project_name']])
            ->unique('id')
            ->values();

        return view('supplier.orders', [
            'supplier' => $supplier,
            'orders' => $orders->values(),
            'filterProjects' => $projects,
        ]);
    }

    public function company(Request $request): View
    {
        $supplier = $this->resolveSupplierForUser((int) $request->user()->id);

        return view('supplier.company', [
            'supplier' => $supplier,
        ]);
    }

    public function updateOrderStatus(Request $request, int $orderId): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(self::ORDER_STATUSES)],
        ]);

        $supplier = $this->resolveSupplierForUser((int) $request->user()->id, true);

        $order = Supplier_orders::query()
            ->where('supplier_id', $supplier->id)
            ->with(['project:id,name', 'designer:id,name,email', 'supplier:id,name'])
            ->findOrFail($orderId);

        $order->status = $data['status'];
        $order->save();

        return response()->json([
            'success' => true,
            'message' => __('supplier-orders.updated'),
            'order' => $this->orderPayload($order, null),
        ]);
    }

    public function saveProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $supplier = $this->resolveSupplierForUser((int) $user->id)
            ?? Supplier::query()->firstOrNew([
                'user_id' => (int) $user->id,
            ]);

        $data = $request->validate([
            'recommend' => ['nullable', 'boolean'],
            'phone' => ['nullable', 'string', 'max:255'],
            'telegram' => ['nullable', 'string', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'sphere' => ['nullable', 'string', 'max:255'],
            'work_terms_type' => ['nullable', Rule::in(['percent', 'amount'])],
            'work_terms_value' => ['nullable', 'string', 'max:255'],
            'brands' => ['nullable', 'array'],
            'brands.*' => ['nullable', 'string', 'max:255'],
            'cities_presence' => ['nullable', 'array'],
            'cities_presence.*' => ['nullable', 'string', 'max:255'],
            'comment_main' => ['nullable', 'string'],
            'org_form' => ['nullable', Rule::in(['ooo', 'ip'])],
            'inn' => ['required', 'string', 'max:32'],
            'kpp' => ['nullable', 'string', 'max:255'],
            'ogrn' => ['nullable', 'string', 'max:255'],
            'okpo' => ['nullable', 'string', 'max:255'],
            'legal_address' => ['nullable', 'string', 'max:1000'],
            'actual_address' => ['nullable', 'string', 'max:1000'],
            'address_match' => ['nullable', 'boolean'],
            'director' => ['nullable', 'string', 'max:255'],
            'accountant' => ['nullable', 'string', 'max:255'],
            'bik' => ['nullable', 'string', 'max:255'],
            'bank' => ['nullable', 'string', 'max:255'],
            'checking_account' => ['nullable', 'string', 'max:255'],
            'corr_account' => ['nullable', 'string', 'max:255'],
            'comment_bank' => ['nullable', 'string'],
            'logo' => ['nullable', 'image', 'max:1024'],
        ]);

        $supplier->name = (string) $user->name;
        $supplier->recommend = $request->boolean('recommend');
        $supplier->phone = $data['phone'] ?? null;
        $supplier->email = (string) $user->email;
        $supplier->telegram = $data['telegram'] ?? null;
        $supplier->whatsapp = $data['whatsapp'] ?? null;
        $supplier->website = $data['website'] ?? null;
        $supplier->city = $data['city'] ?? null;
        $supplier->address = $data['address'] ?? null;
        $supplier->sphere = $data['sphere'] ?? null;
        $supplier->work_terms_type = $data['work_terms_type'] ?? null;
        $supplier->work_terms_value = $data['work_terms_value'] ?? null;
        $supplier->brands = $this->cleanStringArray($request->input('brands', []));
        $supplier->cities_presence = $this->cleanStringArray($request->input('cities_presence', []));
        $supplier->comment = $data['comment_main'] ?? null;
        $supplier->org_form = $data['org_form'] ?? 'ooo';
        $supplier->inn = trim((string) $data['inn']);
        $supplier->kpp = $data['kpp'] ?? null;
        $supplier->ogrn = $data['ogrn'] ?? null;
        $supplier->okpo = $data['okpo'] ?? null;
        $supplier->legal_address = $data['legal_address'] ?? null;
        $supplier->actual_address = $data['actual_address'] ?? null;
        $supplier->address_match = $request->boolean('address_match');
        $supplier->director = $data['director'] ?? null;
        $supplier->accountant = $data['accountant'] ?? null;
        $supplier->bik = $data['bik'] ?? null;
        $supplier->bank = $data['bank'] ?? null;
        $supplier->checking_account = $data['checking_account'] ?? null;
        $supplier->corr_account = $data['corr_account'] ?? null;
        $supplier->comment_bank = $data['comment_bank'] ?? null;

        if ($request->hasFile('logo')) {
            if (! empty($supplier->logo)) {
                Storage::disk('public')->delete($supplier->logo);
            }
            $supplier->logo = PublicFileStorage::store($request->file('logo'), 'suppliers');
        }

        $supplier->profile_status = 'pending';
        $supplier->moderation_status = 'pending';
        $supplier->moderation_comment = null;
        $supplier->moderation_reviewer_id = null;
        $supplier->moderation_reviewed_at = null;
        $supplier->save();

        return redirect()
            ->route('supplier.company')
            ->with('status', __('supplier-portal.submitted_for_review'));
    }

    /**
     * @param  list<array<string, mixed>>|null  $includedSteps
     */
    private function orderPayload(Supplier_orders $order, ?array $includedSteps = null, int $unreadChatCount = 0): array
    {
        $includedSteps ??= $order->includedStepsPayload();

        return [
            'id' => (int) $order->id,
            'number' => (string) $order->id,
            'created_date' => optional($order->created_at)->format('Y-m-d'),
            'project_id' => (int) $order->project_id,
            'project_name' => (string) ($order->project?->name ?? '-'),
            'supplier_id' => (int) $order->supplier_id,
            'supplier_name' => (string) ($order->supplier?->name ?? '-'),
            'designer_name' => (string) ($order->designer?->name ?? '-'),
            'designer_email' => (string) ($order->designer?->email ?? ''),
            'status' => (string) $order->status,
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
            'included_step_ids' => Supplier_orders::normalizeStepIds($order->included_step_ids),
            'included_steps' => $includedSteps,
            'unread_chat_count' => max(0, $unreadChatCount),
        ];
    }

    /**
     * @param  list<int>  $orderIds
     * @return array<int, int>
     */
    private function chatUnreadMapForSupplier(int $supplierUserId, array $orderIds): array
    {
        if (
            $orderIds === []
            || ! Schema::hasTable('supplier_order_messages')
            || ! Schema::hasColumn('supplier_order_messages', 'read_by_supplier_at')
        ) {
            return [];
        }

        $rows = DB::table('supplier_order_messages as m')
            ->whereIn('m.supplier_order_id', $orderIds)
            ->where('m.sender_user_id', '!=', $supplierUserId)
            ->whereNull('m.read_by_supplier_at')
            ->select('m.supplier_order_id', DB::raw('COUNT(*) as unread_count'))
            ->groupBy('m.supplier_order_id')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->supplier_order_id] = (int) $row->unread_count;
        }

        return $map;
    }

    private function cleanStringArray(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(function ($item) {
            return is_string($item) ? trim($item) : '';
        }, $value), fn ($v) => $v !== '')));
    }

    private function resolveSupplierForUser(int $userId, bool $fail = false): ?Supplier
    {
        $supplier = Supplier::query()
            ->where('user_id', $userId)
            ->first();

        if ($supplier) {
            return $supplier;
        }

        if ($fail) {
            abort(404);
        }

        return null;
    }
}
