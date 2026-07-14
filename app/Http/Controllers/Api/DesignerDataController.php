<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\DesignerFavoriteSupplier;
use App\Models\PassportObject;
use App\Models\Project;
use App\Models\Review;
use App\Models\Supplier;
use App\Models\Supplier_orders;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Списки данных кабинета дизайнера (для мобильного приложения).
 * Пока только чтение — без создания/редактирования.
 */
class DesignerDataController extends Controller
{
    /**
     * GET /api/clients
     * Мои клиенты
     */
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
            ->map(fn (Client $c) => [
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
            ]);

        return response()->json([
            'success' => true,
            'data' => $clients,
        ]);
    }

    /**
     * GET /api/objects
     * Паспорт объекта
     */
    public function objects(Request $request): JsonResponse
    {
        $this->ensureDesigner($request);
        $userId = (int) $request->user()->id;

        $objects = PassportObject::query()
            ->where('user_id', $userId)
            ->with('client:id,full_name')
            ->orderByDesc('id')
            ->get()
            ->map(function (PassportObject $o) {
                $workflowStatus = match ((string) ($o->moderation_status ?? '')) {
                    'pending' => 'in_moderation',
                    'rejected' => 'rejected',
                    default => (string) $o->status,
                };

                return [
                    'id' => $o->id,
                    'client_id' => $o->client_id,
                    'client_name' => $o->client?->full_name,
                    'city' => $o->city,
                    'address' => $o->address,
                    'apartment' => $o->apartment,
                    'type' => $o->type,
                    'status' => $o->status,
                    'workflow_status' => $workflowStatus,
                    'area' => $o->area,
                    'repair_budget_planned' => $o->repair_budget_planned,
                    'repair_budget_actual' => $o->repair_budget_actual,
                    'comment' => $o->comment,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $objects,
        ]);
    }

    /**
     * GET /api/projects
     * Проекты
     */
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
            ->map(function (Project $p) {
                $workflowStatus = match ((string) ($p->moderation_status ?? '')) {
                    'pending' => 'in_moderation',
                    'rejected' => 'rejected',
                    default => (string) $p->status,
                };

                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'object_id' => $p->object_id,
                    'object_address' => $p->object?->address,
                    'object_city' => $p->object?->city,
                    'client_name' => $p->object?->client?->full_name,
                    'status' => $p->status,
                    'workflow_status' => $workflowStatus,
                    'start_date' => optional($p->start_date)->format('Y-m-d'),
                    'planned_end_date' => optional($p->planned_end_date)->format('Y-m-d'),
                    'actual_end_date' => optional($p->actual_end_date)->format('Y-m-d'),
                    'planned_cost' => (float) $p->planned_cost,
                    'actual_cost' => (float) $p->actual_cost,
                    'comment' => $p->comment,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $projects,
        ]);
    }

    /**
     * GET /api/supplier-orders
     * Поставки
     */
    public function supplierOrders(Request $request): JsonResponse
    {
        $this->ensureDesigner($request);
        $userId = (int) $request->user()->id;

        $orders = Supplier_orders::query()
            ->where('user_id', $userId)
            ->with(['project:id,name', 'supplier:id,name'])
            ->orderByDesc('id')
            ->get()
            ->map(function (Supplier_orders $o) {
                $status = (string) $o->status;
                $workflowStatus = (bool) $o->is_sent_to_supplier ? $status : 'draft';

                return [
                    'id' => (int) $o->id,
                    'project_id' => (int) $o->project_id,
                    'project_name' => (string) ($o->project?->name ?? ''),
                    'supplier_id' => (int) $o->supplier_id,
                    'supplier_name' => (string) ($o->supplier?->name ?? ''),
                    'status' => $status,
                    'workflow_status' => $workflowStatus,
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
            });

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * GET /api/suppliers
     * Поставщики (публичные + свои)
     */
    public function suppliers(Request $request): JsonResponse
    {
        $this->ensureDesigner($request);
        $userId = (int) $request->user()->id;

        $suppliers = Supplier::query()
            ->where(function ($q) use ($userId) {
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
            })
            ->orderByDesc('id')
            ->get();

        $supplierIds = $suppliers->pluck('id')->map(fn ($id) => (int) $id)->all();

        $favoriteIds = $supplierIds === []
            ? []
            : DesignerFavoriteSupplier::query()
                ->where('designer_user_id', $userId)
                ->whereIn('supplier_id', $supplierIds)
                ->pluck('supplier_id')
                ->mapWithKeys(fn ($id) => [(int) $id => true])
                ->all();

        $ratings = Review::supplierRatingSummaries($supplierIds);

        $data = $suppliers->map(function (Supplier $s) use ($userId, $favoriteIds, $ratings) {
            $isOwned = (int) $s->created_by_user_id === $userId
                || ((int) $s->user_id === $userId && $s->created_by_user_id === null);

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
                'is_owned_by_designer' => $isOwned,
                'is_favorite' => isset($favoriteIds[(int) $s->id]),
                'rating' => $ratings[(int) $s->id] ?? ['average' => null, 'count' => 0],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    private function ensureDesigner(Request $request): void
    {
        $role = (string) ($request->user()->role ?? '');

        if (! in_array($role, ['designer', 'moderator'], true)) {
            abort(403, 'Only designer portal');
        }
    }
}
