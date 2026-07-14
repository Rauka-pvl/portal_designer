<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\DesignerFavoriteSupplier;
use App\Models\PassportObject;
use App\Models\Project;
use App\Models\ProjectStages;
use App\Models\ProjectStageStep;
use App\Models\Review;
use App\Models\Supplier;
use App\Models\Supplier_orders;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Списки и детали кабинета дизайнера (для мобильного приложения).
 * Пока только чтение — без создания/редактирования.
 */
class DesignerDataController extends Controller
{
    // ──────────────────────────────────────────────
    // Клиенты
    // ──────────────────────────────────────────────

    /** GET /api/clients */
    public function clients(Request $request): JsonResponse
    {
        $this->ensureDesigner($request);
        $userId = (int) $request->user()->id;

        $clients = Client::query()
            ->where('user_id', $userId)
            ->withCount('objects as count_objects')
            ->withSum('objects as sum_repair_budget_planned', 'repair_budget_planned')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Client $c) => $this->clientListPayload($c));

        return $this->ok($clients);
    }

    /** GET /api/clients/{id} */
    public function client(Request $request, int $id): JsonResponse
    {
        $this->ensureDesigner($request);
        $userId = (int) $request->user()->id;

        $client = Client::query()
            ->where('user_id', $userId)
            ->withCount('objects as count_objects')
            ->withSum('objects as sum_repair_budget_planned', 'repair_budget_planned')
            ->with(['objects' => fn ($q) => $q->orderByDesc('id')])
            ->findOrFail($id);

        return $this->ok($this->clientDetailPayload($client));
    }

    // ──────────────────────────────────────────────
    // Объекты
    // ──────────────────────────────────────────────

    /** GET /api/objects */
    public function objects(Request $request): JsonResponse
    {
        $this->ensureDesigner($request);
        $userId = (int) $request->user()->id;

        $objects = PassportObject::query()
            ->where('user_id', $userId)
            ->with('client:id,full_name')
            ->orderByDesc('id')
            ->get()
            ->map(fn (PassportObject $o) => $this->objectListPayload($o));

        return $this->ok($objects);
    }

    /** GET /api/objects/{id} */
    public function object(Request $request, int $id): JsonResponse
    {
        $this->ensureDesigner($request);
        $userId = (int) $request->user()->id;

        $object = PassportObject::query()
            ->where('user_id', $userId)
            ->with('client:id,full_name')
            ->findOrFail($id);

        return $this->ok($this->objectDetailPayload($object));
    }

    // ──────────────────────────────────────────────
    // Проекты
    // ──────────────────────────────────────────────

    /** GET /api/projects */
    public function projects(Request $request): JsonResponse
    {
        $this->ensureDesigner($request);
        $userId = (int) $request->user()->id;

        $projects = Project::query()
            ->where('user_id', $userId)
            ->with([
                'object:id,address,city,client_id',
                'object.client:id,full_name',
            ])
            ->orderByDesc('id')
            ->get()
            ->map(fn (Project $p) => $this->projectListPayload($p));

        return $this->ok($projects);
    }

    /** GET /api/projects/{id} */
    public function project(Request $request, int $id): JsonResponse
    {
        $this->ensureDesigner($request);
        $userId = (int) $request->user()->id;

        $project = Project::query()
            ->where('user_id', $userId)
            ->with([
                'object:id,address,city,client_id',
                'object.client:id,full_name',
                'stages.steps',
                'stages.template',
            ])
            ->findOrFail($id);

        return $this->ok($this->projectDetailPayload($project));
    }

    // ──────────────────────────────────────────────
    // Поставки
    // ──────────────────────────────────────────────

    /** GET /api/supplier-orders */
    public function supplierOrders(Request $request): JsonResponse
    {
        $this->ensureDesigner($request);
        $userId = (int) $request->user()->id;

        $orders = Supplier_orders::query()
            ->where('user_id', $userId)
            ->with(['project:id,name', 'supplier:id,name'])
            ->orderByDesc('id')
            ->get()
            ->map(fn (Supplier_orders $o) => $this->orderListPayload($o));

        return $this->ok($orders);
    }

    /** GET /api/supplier-orders/{id} */
    public function supplierOrder(Request $request, int $id): JsonResponse
    {
        $this->ensureDesigner($request);
        $userId = (int) $request->user()->id;

        $order = Supplier_orders::query()
            ->where('user_id', $userId)
            ->with(['project:id,name', 'supplier:id,name'])
            ->findOrFail($id);

        return $this->ok($this->orderDetailPayload($order));
    }

    // ──────────────────────────────────────────────
    // Поставщики
    // ──────────────────────────────────────────────

    /** GET /api/suppliers */
    public function suppliers(Request $request): JsonResponse
    {
        $this->ensureDesigner($request);
        $userId = (int) $request->user()->id;

        $suppliers = $this->visibleSuppliersQuery($userId)
            ->orderByDesc('id')
            ->get();

        $supplierIds = $suppliers->pluck('id')->map(fn ($id) => (int) $id)->all();
        $favoriteIds = $this->favoriteLookup($userId, $supplierIds);
        $ratings = Review::supplierRatingSummaries($supplierIds);

        $data = $suppliers->map(fn (Supplier $s) => $this->supplierListPayload($s, $userId, $favoriteIds, $ratings));

        return $this->ok($data);
    }

    /** GET /api/suppliers/{id} */
    public function supplier(Request $request, int $id): JsonResponse
    {
        $this->ensureDesigner($request);
        $userId = (int) $request->user()->id;

        $supplier = $this->visibleSuppliersQuery($userId)->findOrFail($id);

        $favoriteIds = $this->favoriteLookup($userId, [(int) $supplier->id]);
        $rating = Review::supplierRatingSummary((int) $supplier->id);
        $recentReviews = Review::recentForSupplier((int) $supplier->id, 5);

        return $this->ok($this->supplierDetailPayload($supplier, $userId, $favoriteIds, $rating, $recentReviews));
    }

    // ──────────────────────────────────────────────
    // Payloads — клиенты
    // ──────────────────────────────────────────────

    private function clientListPayload(Client $c): array
    {
        return [
            'id' => $c->id,
            'full_name' => $c->full_name,
            'client_type' => $c->client_type,
            'phone' => $c->phone,
            'email' => $c->email,
            'status' => $c->status,
            'comment' => $c->comment,
            'link' => $c->link,
            'count_objects' => (int) ($c->count_objects ?? 0),
            'sum_repair_budget_planned' => (float) ($c->sum_repair_budget_planned ?? 0),
        ];
    }

    private function clientDetailPayload(Client $c): array
    {
        $files = $this->decodeJsonList($c->file_paths);
        if ($files === [] && ! empty($c->file_path)) {
            $files = [$c->file_path];
        }

        return [
            ...$this->clientListPayload($c),
            'file_path' => $c->file_path,
            'file_paths' => $files,
            'file_items' => $this->fileItems($files),
            'objects' => $c->objects->map(fn (PassportObject $o) => [
                'id' => $o->id,
                'city' => $o->city,
                'address' => $o->address,
                'type' => $o->type,
                'status' => $o->status,
                'area' => $o->area,
                'repair_budget_planned' => $o->repair_budget_planned,
            ])->values(),
            'created_at' => optional($c->created_at)->toIso8601String(),
            'updated_at' => optional($c->updated_at)->toIso8601String(),
        ];
    }

    // ──────────────────────────────────────────────
    // Payloads — объекты
    // ──────────────────────────────────────────────

    private function objectListPayload(PassportObject $o): array
    {
        return [
            'id' => $o->id,
            'client_id' => $o->client_id,
            'client_name' => $o->client?->full_name,
            'city' => $o->city,
            'address' => $o->address,
            'apartment' => $o->apartment,
            'type' => $o->type,
            'status' => $o->status,
            'workflow_status' => $this->workflowStatus($o->moderation_status, $o->status),
            'area' => $o->area,
            'repair_budget_planned' => $o->repair_budget_planned,
            'repair_budget_actual' => $o->repair_budget_actual,
            'comment' => $o->comment,
        ];
    }

    private function objectDetailPayload(PassportObject $o): array
    {
        $links = $this->decodeJsonList($o->links);
        $files = $this->decodeJsonList($o->file_paths);

        return [
            ...$this->objectListPayload($o),
            'apartment_floor' => $o->apartment_floor,
            'apartment_entrance' => $o->apartment_entrance,
            'repair_budget_per_m2_planned' => $o->repair_budget_per_m2_planned,
            'repair_budget_per_m2_actual' => $o->repair_budget_per_m2_actual,
            'links' => $links,
            'file_paths' => $files,
            'file_items' => $this->fileItems($files),
            'latitude' => $o->latitude,
            'longitude' => $o->longitude,
            'moderation_status' => $o->moderation_status,
            'moderation_duplicate_of_object_id' => $o->moderation_duplicate_of_object_id,
            'created_at' => optional($o->created_at)->toIso8601String(),
            'updated_at' => optional($o->updated_at)->toIso8601String(),
        ];
    }

    // ──────────────────────────────────────────────
    // Payloads — проекты
    // ──────────────────────────────────────────────

    private function projectListPayload(Project $p): array
    {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'object_id' => $p->object_id,
            'object_address' => $p->object?->address,
            'object_city' => $p->object?->city,
            'client_name' => $p->object?->client?->full_name,
            'status' => $p->status,
            'workflow_status' => $this->workflowStatus($p->moderation_status, $p->status),
            'start_date' => optional($p->start_date)->format('Y-m-d'),
            'planned_end_date' => optional($p->planned_end_date)->format('Y-m-d'),
            'actual_end_date' => optional($p->actual_end_date)->format('Y-m-d'),
            'planned_cost' => (float) $p->planned_cost,
            'actual_cost' => (float) $p->actual_cost,
            'comment' => $p->comment,
        ];
    }

    private function projectDetailPayload(Project $p): array
    {
        $files = is_array($p->files) ? $p->files : [];

        return [
            ...$this->projectListPayload($p),
            'moderation_status' => $p->moderation_status,
            'moderation_reason' => $p->moderation_reason,
            'moderation_comment' => $p->moderation_comment,
            'links' => is_array($p->links) ? $p->links : [],
            'files' => $files,
            'file_items' => $this->fileItems($files),
            'stages' => $p->stages->map(function (ProjectStages $stage) {
                $type = (string) $stage->stage_type;
                $labelKey = 'projects.stage_'.$type;
                $stageLabel = $type !== '' ? (string) __($labelKey) : '';
                if ($stageLabel === $labelKey) {
                    $stageLabel = $type;
                }

                return [
                    'id' => $stage->id,
                    'stage_type' => $stage->stage_type,
                    'stage_type_label' => $stageLabel,
                    'template_id' => $stage->template_id,
                    'deadline' => $stage->deadline,
                    'responsible_id' => $stage->responsible_id,
                    'assign_task' => (bool) $stage->assign_task,
                    'steps' => $stage->steps->sortBy('order')->map(fn (ProjectStageStep $step) => [
                        'id' => $step->id,
                        'title' => $step->title,
                        'deadline' => $step->deadline,
                        'responsible_id' => $step->responsible_id,
                        'link' => $step->link,
                        'result_status' => $step->result_status ?? 'pending',
                        'result_comment' => $step->result_comment,
                    ])->values(),
                ];
            })->values(),
            'created_at' => optional($p->created_at)->toIso8601String(),
            'updated_at' => optional($p->updated_at)->toIso8601String(),
        ];
    }

    // ──────────────────────────────────────────────
    // Payloads — поставки
    // ──────────────────────────────────────────────

    private function orderListPayload(Supplier_orders $o): array
    {
        $status = (string) $o->status;

        return [
            'id' => (int) $o->id,
            'project_id' => (int) $o->project_id,
            'project_name' => (string) ($o->project?->name ?? ''),
            'supplier_id' => (int) $o->supplier_id,
            'supplier_name' => (string) ($o->supplier?->name ?? ''),
            'status' => $status,
            'workflow_status' => (bool) $o->is_sent_to_supplier ? $status : 'draft',
            'is_sent_to_supplier' => (bool) $o->is_sent_to_supplier,
            'summa' => (int) $o->summa,
            'category' => (string) ($o->category ?? ''),
            'mark' => (string) ($o->mark ?? ''),
            'room' => (string) ($o->room ?? ''),
            'date_planned' => optional($o->date_planned)->format('Y-m-d'),
            'date_actual' => optional($o->date_actual)->format('Y-m-d'),
            'comment' => (string) ($o->comment ?? ''),
            'bonus_percent' => $o->bonus_percent !== null ? (float) $o->bonus_percent : null,
            'bonus_amount' => $o->bonusAmount(),
            'offer_status' => $o->effectiveOfferStatus(),
            'product_items' => is_array($o->product_items) ? $o->product_items : [],
            'created_at' => optional($o->created_at)->toIso8601String(),
        ];
    }

    private function orderDetailPayload(Supplier_orders $o): array
    {
        $files = is_array($o->files) ? $o->files : [];

        return [
            ...$this->orderListPayload($o),
            'prepayment_date' => optional($o->prepayment_date)->format('Y-m-d'),
            'payment_date' => optional($o->payment_date)->format('Y-m-d'),
            'prepayment_amount' => $o->prepayment_amount !== null ? (int) $o->prepayment_amount : null,
            'payment_amount' => $o->payment_amount !== null ? (int) $o->payment_amount : null,
            'links' => is_array($o->links) ? $o->links : [],
            'files' => $files,
            'file_items' => $this->fileItems($files),
            'included_step_ids' => Supplier_orders::normalizeStepIds($o->included_step_ids),
            'included_steps' => $o->includedStepsPayload(),
            ...$o->offerPayload('designer'),
            'updated_at' => optional($o->updated_at)->toIso8601String(),
        ];
    }

    // ──────────────────────────────────────────────
    // Payloads — поставщики
    // ──────────────────────────────────────────────

    private function supplierListPayload(Supplier $s, int $userId, array $favoriteIds, array $ratings): array
    {
        return [
            'id' => $s->id,
            'name' => $s->name,
            'phone' => $s->phone,
            'email' => $s->email,
            'city' => $s->city,
            'address' => $s->address,
            'sphere' => $s->sphere,
            'brands' => is_array($s->brands) ? $s->brands : [],
            'profile_status' => $s->profile_status,
            'moderation_status' => $s->moderation_status,
            'recommend' => (bool) $s->recommend,
            'logo_url' => $s->logo ? asset('storage/'.$s->logo) : null,
            'is_owned_by_designer' => $this->isOwnedByDesigner($s, $userId),
            'is_favorite' => isset($favoriteIds[(int) $s->id]),
            'rating' => $ratings[(int) $s->id] ?? ['average' => null, 'count' => 0],
        ];
    }

    private function supplierDetailPayload(
        Supplier $s,
        int $userId,
        array $favoriteIds,
        array $rating,
        $recentReviews
    ): array {
        $sphere = $s->sphere;
        $sphereDisplay = $sphere;
        if (is_string($sphere) && trim($sphere) !== '') {
            $translated = __('supplier_spheres.'.$sphere);
            $sphereDisplay = $translated !== 'supplier_spheres.'.$sphere ? $translated : $sphere;
        }

        $isOwned = $this->isOwnedByDesigner($s, $userId);

        return [
            'id' => $s->id,
            'name' => $s->name,
            'phone' => $s->phone,
            'email' => $s->email,
            'telegram' => $s->telegram,
            'whatsapp' => $s->whatsapp,
            'website' => $s->website,
            'city' => $s->city,
            'address' => $s->address,
            'sphere' => $sphere,
            'sphere_display' => $sphereDisplay,
            'work_terms_type' => $s->work_terms_type,
            'work_terms_value' => $s->work_terms_value,
            'brands' => is_array($s->brands) ? $s->brands : [],
            'cities_presence' => is_array($s->cities_presence) ? $s->cities_presence : [],
            'comment' => $s->comment,
            'profile_status' => $s->profile_status,
            'moderation_status' => $s->moderation_status,
            'moderation_comment' => $s->moderation_comment,
            'recommend' => (bool) $s->recommend,
            'logo' => $s->logo,
            'logo_url' => $s->logo ? asset('storage/'.$s->logo) : null,
            'org_form' => $s->org_form,
            'inn' => $s->inn,
            'kpp' => $s->kpp,
            'ogrn' => $s->ogrn,
            'okpo' => $s->okpo,
            'legal_address' => $s->legal_address,
            'actual_address' => $s->actual_address,
            'address_match' => (bool) $s->address_match,
            'director' => $s->director,
            'accountant' => $s->accountant,
            'bik' => $s->bik,
            'bank' => $s->bank,
            'checking_account' => $s->checking_account,
            'corr_account' => $s->corr_account,
            'comment_bank' => $s->comment_bank,
            'is_owned_by_designer' => $isOwned,
            'is_favorite' => isset($favoriteIds[(int) $s->id]),
            'rating' => $rating,
            'recent_reviews' => $recentReviews->map(fn (Review $r) => [
                'author' => (string) ($r->reviewer->name ?? ''),
                'rating' => (int) $r->rating,
                'comment' => (string) ($r->comment ?? ''),
                'date' => optional($r->created_at)->format('Y-m-d'),
            ])->values(),
            'created_at' => optional($s->created_at)->toIso8601String(),
            'updated_at' => optional($s->updated_at)->toIso8601String(),
        ];
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    private function ensureDesigner(Request $request): void
    {
        $role = (string) ($request->user()->role ?? '');

        if (! in_array($role, ['designer', 'moderator'], true)) {
            abort(403, 'Only designer portal');
        }
    }

    private function ok(mixed $data): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    private function workflowStatus(?string $moderationStatus, mixed $status): string
    {
        return match ((string) ($moderationStatus ?? '')) {
            'pending' => 'in_moderation',
            'rejected' => 'rejected',
            default => (string) $status,
        };
    }

    private function visibleSuppliersQuery(int $userId): Builder
    {
        return Supplier::query()->where(function ($q) use ($userId) {
            $q->where(function ($q2) {
                $q2->where('profile_status', 'active')
                    ->where('moderation_status', 'approved');
            })->orWhere(function ($q2) use ($userId) {
                $q2->where('created_by_user_id', $userId)
                    ->orWhere(function ($legacy) use ($userId) {
                        $legacy->whereNull('created_by_user_id')
                            ->where('user_id', $userId);
                    });
            });
        });
    }

    private function isOwnedByDesigner(Supplier $s, int $userId): bool
    {
        return (int) $s->created_by_user_id === $userId
            || ((int) $s->user_id === $userId && $s->created_by_user_id === null);
    }

    /** @param list<int> $supplierIds */
    private function favoriteLookup(int $userId, array $supplierIds): array
    {
        if ($supplierIds === []) {
            return [];
        }

        return DesignerFavoriteSupplier::query()
            ->where('designer_user_id', $userId)
            ->whereIn('supplier_id', $supplierIds)
            ->pluck('supplier_id')
            ->mapWithKeys(fn ($id) => [(int) $id => true])
            ->all();
    }

    /** @return list<string> */
    private function decodeJsonList(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value, fn ($v) => is_string($v) && $v !== ''));
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);

            return is_array($decoded)
                ? array_values(array_filter($decoded, fn ($v) => is_string($v) && $v !== ''))
                : [];
        }

        return [];
    }

    /** @param list<string> $paths */
    private function fileItems(array $paths): array
    {
        return collect($paths)
            ->filter(fn ($f) => is_string($f) && trim($f) !== '')
            ->map(fn ($f) => [
                'path' => $f,
                'name' => basename($f),
                'url' => asset('storage/'.ltrim($f, '/')),
            ])
            ->values()
            ->all();
    }
}
