<?php

namespace App\Http\Controllers\Moderator;

use App\Http\Controllers\Controller;
use App\Models\PassportObject;
use App\Models\Supplier;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ModeratorController extends Controller
{
    public function index()
    {
        $pendingSuppliers = Supplier::query()
            ->where('moderation_status', 'pending')
            ->orderByDesc('id')
            ->with(['user:id,name'])
            ->limit(50)
            ->get(['id', 'name', 'user_id', 'city', 'address', 'moderation_status', 'moderation_comment']);

        $pendingObjects = PassportObject::query()
            ->where('moderation_status', 'pending')
            ->whereNotNull('moderation_duplicate_of_object_id')
            ->orderByDesc('id')
            ->with([
                'user:id,name',
                'moderationDuplicateOf.user:id,name',
            ])
            ->limit(50)
            ->get([
                'id',
                'user_id',
                'city',
                'address',
                'type',
                'apartment',
                'apartment_floor',
                'apartment_entrance',
                'moderation_status',
                'moderation_duplicate_of_object_id',
            ]);

        return view('moderator.index', [
            'pendingSuppliers' => $pendingSuppliers,
            'pendingObjects' => $pendingObjects,
        ]);
    }

    public function history(Request $request)
    {
        $type = (string) $request->query('type', 'all');
        if (! in_array($type, ['all', 'suppliers', 'objects'], true)) {
            $type = 'all';
        }

        $q = trim((string) $request->query('q', ''));

        $statusFilter = (string) $request->query('status', 'all');
        if (! in_array($statusFilter, ['all', 'approved', 'rejected'], true)) {
            $statusFilter = 'all';
        }

        $sort = (string) $request->query('sort', 'reviewed_desc');
        $allowedSort = ['reviewed_desc', 'reviewed_asc', 'type_asc', 'type_desc', 'status_asc', 'status_desc'];
        if (! in_array($sort, $allowedSort, true)) {
            $sort = 'reviewed_desc';
        }

        $supplierRows = collect();
        if (in_array($type, ['all', 'suppliers'], true)) {
            $suppliersQuery = Supplier::query()
                ->whereIn('moderation_status', ['approved', 'rejected'])
                ->with('user:id,name');

            if ($q !== '') {
                $like = '%'.$q.'%';
                $suppliersQuery->where(function ($sub) use ($like) {
                    $sub->where('name', 'like', $like)
                        ->orWhere('city', 'like', $like)
                        ->orWhere('address', 'like', $like)
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like));
                });
            }
            if ($statusFilter !== 'all') {
                $suppliersQuery->where('moderation_status', $statusFilter);
            }

            $supplierRows = $suppliersQuery->get()->map(fn (Supplier $s) => [
                'kind' => 'supplier',
                'id' => $s->id,
                'label' => $s->name,
                'line2' => trim((string) (($s->city ?? '').' '.($s->address ?? ''))),
                'designer' => $s->user?->name,
                'status' => (string) $s->moderation_status,
                'reviewed_at' => $s->moderation_reviewed_at,
                'comment' => $s->moderation_comment,
            ]);
        }

        $objectRows = collect();
        if (in_array($type, ['all', 'objects'], true)) {
            $objectsQuery = PassportObject::withTrashed()
                ->whereIn('moderation_status', ['approved', 'rejected'])
                ->with(['user:id,name', 'moderationDuplicateOf.user:id,name']);

            if ($q !== '') {
                $like = '%'.$q.'%';
                $objectsQuery->where(function ($sub) use ($like) {
                    $sub->where('city', 'like', $like)
                        ->orWhere('address', 'like', $like)
                        ->orWhere('apartment', 'like', $like)
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like));
                });
            }
            if ($statusFilter !== 'all') {
                $objectsQuery->where('moderation_status', $statusFilter);
            }

            $objectRows = $objectsQuery->get()->map(function (PassportObject $o) {
                $addr = trim((string) (($o->city ?? '').' '.($o->address ?? '').' '.($o->apartment ?? '')));

                return [
                    'kind' => 'object',
                    'id' => $o->id,
                    'label' => $addr !== '' ? $addr : '#'.$o->id,
                    'line2' => (string) ($o->type ?? ''),
                    'designer' => $o->user?->name,
                    'status' => (string) $o->moderation_status,
                    'reviewed_at' => $o->moderation_reviewed_at,
                    'comment' => $o->moderation_comment,
                    'trashed' => $o->trashed(),
                ];
            });
        }

        $merged = $supplierRows->concat($objectRows);

        $merged = match ($sort) {
            'reviewed_asc' => $merged->sortBy(fn (array $r) => $r['reviewed_at']?->getTimestamp() ?? 0)->values(),
            'reviewed_desc' => $merged->sortByDesc(fn (array $r) => $r['reviewed_at']?->getTimestamp() ?? 0)->values(),
            'type_asc' => $merged->sortBy('kind')->values(),
            'type_desc' => $merged->sortByDesc('kind')->values(),
            'status_asc' => $merged->sortBy('status')->values(),
            'status_desc' => $merged->sortByDesc('status')->values(),
            default => $merged->sortByDesc(fn (array $r) => $r['reviewed_at']?->getTimestamp() ?? 0)->values(),
        };

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));
        $total = $merged->count();
        $slice = $merged->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('moderator.history', [
            'items' => $paginator,
            'filters' => [
                'type' => $type,
                'q' => $q,
                'status' => $statusFilter,
                'sort' => $sort,
                'page' => $page > 1 ? (string) $page : '',
            ],
        ]);
    }

    public function historySupplierUpdate(Request $request, int $supplierId): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'comment' => ['nullable', 'string', 'max:5000'],
        ]);

        Supplier::query()
            ->whereIn('moderation_status', ['approved', 'rejected'])
            ->whereKey($supplierId)
            ->firstOrFail();

        $comment = isset($data['comment']) && trim((string) $data['comment']) !== ''
            ? $data['comment']
            : null;

        DB::table('suppliers')
            ->where('id', $supplierId)
            ->update([
                'moderation_status' => $data['decision'],
                'profile_status' => $data['decision'] === 'approved' ? 'active' : 'rejected',
                'moderation_comment' => $comment,
                'moderation_reviewer_id' => $request->user()->id,
                'moderation_reviewed_at' => now(),
            ]);

        $supplier = Supplier::query()->whereKey($supplierId)->first(['id', 'user_id', 'account_user_id', 'name']);
        if ($supplier) {
            $this->createSupplierNotification($supplier, (string) $data['decision'], $comment);
        }

        return $this->redirectToHistory($request)->with('status', __('moderation.saved'));
    }

    public function historyObjectUpdate(Request $request, int $objectId): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'comment' => ['nullable', 'string', 'max:5000'],
        ]);

        $object = PassportObject::withTrashed()
            ->whereIn('moderation_status', ['approved', 'rejected'])
            ->whereKey($objectId)
            ->firstOrFail();

        $comment = isset($data['comment']) && trim((string) $data['comment']) !== ''
            ? $data['comment']
            : null;

        if ($data['decision'] === 'approved') {
            if ($object->trashed()) {
                $object->restore();
            }
            $object->moderation_status = 'approved';
            $object->moderation_duplicate_of_object_id = null;
            $object->moderation_comment = $comment;
            $object->moderation_reviewer_id = $request->user()->id;
            $object->moderation_reviewed_at = now();
            $object->save();
        } else {
            $object->moderation_comment = $comment;
            $object->moderation_reviewer_id = $request->user()->id;
            $object->moderation_reviewed_at = now();
            $object->moderation_status = 'rejected';
            $object->save();
        }

        $this->createObjectNotification($object, (string) $data['decision'], $comment);

        return $this->redirectToHistory($request)->with('status', __('moderation.saved'));
    }

    private function redirectToHistory(Request $request): RedirectResponse
    {
        $query = array_filter([
            'type' => $request->input('_r_type'),
            'q' => $request->input('_r_q'),
            'status' => $request->input('_r_status'),
            'sort' => $request->input('_r_sort'),
            'page' => $request->input('_r_page'),
        ], fn ($v) => $v !== null && $v !== '');

        return redirect()->route('moderator.history', $query);
    }

    public function supplierShow(int $supplierId)
    {
        $supplier = Supplier::query()
            ->where('id', $supplierId)
            ->with('user:id,name')
            ->findOrFail($supplierId);

        return view('moderator.suppliers.show', [
            'supplier' => $supplier,
        ]);
    }

    public function supplierDecide(Request $request, int $supplierId)
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'comment' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::table('suppliers')
            ->where('id', $supplierId)
            ->update([
                'moderation_status' => $data['decision'],
                'profile_status' => $data['decision'] === 'approved' ? 'active' : 'rejected',
                'moderation_comment' => isset($data['comment']) && trim((string) $data['comment']) !== ''
                    ? $data['comment']
                    : null,
                'moderation_reviewer_id' => $request->user()->id,
                'moderation_reviewed_at' => now(),
            ]);

        $supplier = Supplier::query()->whereKey($supplierId)->first(['id', 'user_id', 'account_user_id', 'name']);
        if ($supplier) {
            $this->createSupplierNotification($supplier, (string) $data['decision'], $data['comment'] ?? null);
        }

        return redirect()->route('moderator.suppliers.show', $supplierId)->with('status', __('moderation.saved'));
    }

    public function objectShow(int $objectId)
    {
        $object = PassportObject::withTrashed()
            ->where('id', $objectId)
            ->with([
                'user:id,name',
                'moderationDuplicateOf.user:id,name',
            ])
            ->findOrFail($objectId);

        return view('moderator.objects.show', [
            'object' => $object,
            'existingObject' => $object->moderationDuplicateOf,
        ]);
    }

    public function objectDecide(Request $request, int $objectId)
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'comment' => ['nullable', 'string', 'max:5000'],
        ]);

        $object = PassportObject::query()
            ->where('id', $objectId)
            ->where('moderation_status', 'pending')
            ->whereNotNull('moderation_duplicate_of_object_id')
            ->findOrFail($objectId);

        $comment = isset($data['comment']) && trim((string) $data['comment']) !== ''
            ? $data['comment']
            : null;

        if ($data['decision'] === 'approved') {
            $object->moderation_status = 'approved';
            $object->moderation_duplicate_of_object_id = null;
            $object->moderation_comment = $comment;
            $object->moderation_reviewer_id = $request->user()->id;
            $object->moderation_reviewed_at = now();
            $object->save();
        } else {
            $object->moderation_comment = $comment;
            $object->moderation_reviewer_id = $request->user()->id;
            $object->moderation_reviewed_at = now();
            $object->moderation_status = 'rejected';
            $object->save();
        }

        $this->createObjectNotification($object, (string) $data['decision'], $comment);

        return redirect()->route('moderator.index')->with('status', __('moderation.saved'));
    }

    private function createSupplierNotification(Supplier $supplier, string $decision, ?string $comment): void
    {
        $targetUserId = (int) ($supplier->account_user_id ?: $supplier->user_id);
        if ($targetUserId < 1) {
            return;
        }

        $label = trim((string) ($supplier->name ?? ''));
        $statusLabel = $decision === 'approved'
            ? __('notifications.status_approved')
            : __('notifications.status_rejected');

        UserNotification::query()->create([
            'user_id' => $targetUserId,
            'title' => __('notifications.supplier_title', ['status' => $statusLabel]),
            'comment' => __('notifications.supplier_comment', ['name' => $label !== '' ? $label : '#'.$supplier->id])."\n".$comment,
        ]);
    }

    private function createObjectNotification(PassportObject $object, string $decision, ?string $comment): void
    {
        if ((int) $object->user_id < 1) {
            return;
        }

        $label = trim((string) (($object->city ?? '').' '.($object->address ?? '').' '.($object->apartment ?? '')));
        $statusLabel = $decision === 'approved'
            ? __('notifications.status_approved')
            : __('notifications.status_rejected');

        UserNotification::query()->create([
            'user_id' => (int) $object->user_id,
            'title' => __('notifications.object_title', ['status' => $statusLabel]),
            'comment' => __('notifications.object_comment', [
                'name' => $label !== '' ? $label : '#'.$object->id,
            ])."\n".$comment,
        ]);
    }
}
