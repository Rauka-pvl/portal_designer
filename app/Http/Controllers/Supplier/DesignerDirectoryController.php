<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Supplier;
use App\Models\Supplier_orders;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DesignerDirectoryController extends Controller
{
    public function index(Request $request): View
    {
        $supplier = $this->supplierForUser($request);

        $workedWithIds = $this->designerIdsForSupplier($supplier);
        $workedWithLookup = array_fill_keys($workedWithIds, true);

        $users = User::query()
            ->where('role', 'designer')
            ->with('designerProfile:id,user_id,city,short_description,specialization')
            ->orderBy('name')
            ->get();

        $ratingSummaries = Review::designerRatingSummaries(
            $users->pluck('id')->map(fn ($id) => (int) $id)->all()
        );

        $designers = $users->map(fn (User $u) => [
            'id' => (int) $u->id,
            'name' => (string) $u->name,
            'city' => (string) ($u->designerProfile->city ?? ''),
            'specialization' => (string) ($u->designerProfile->specialization ?? ''),
            'short_description' => (string) ($u->designerProfile->short_description ?? ''),
            'worked_with' => isset($workedWithLookup[(int) $u->id]),
            'rating' => $ratingSummaries[(int) $u->id] ?? ['average' => null, 'count' => 0],
        ])->values();

        return view('supplier.designers.index', [
            'designers' => $designers,
            'workedWithCount' => count($workedWithIds),
        ]);
    }

    public function show(Request $request, int $designerId): View
    {
        $this->supplierForUser($request);

        $designer = User::query()
            ->where('role', 'designer')
            ->with('designerProfile')
            ->findOrFail($designerId);

        return view('supplier.designers.show', [
            'designer' => $designer,
            'profile' => $designer->designerProfile,
            'ratingSummary' => Review::designerRatingSummary($designerId),
        ]);
    }

    public function reviews(Request $request, int $designerId): View
    {
        $this->supplierForUser($request);

        $designer = User::query()
            ->where('role', 'designer')
            ->findOrFail($designerId);

        $reviews = Review::query()
            ->where('direction', Review::DIRECTION_SUPPLIER_TO_DESIGNER)
            ->where('designer_user_id', $designerId)
            ->with('reviewer:id,name')
            ->orderByDesc('id')
            ->paginate(10);

        return view('supplier.designers.reviews', [
            'designer' => $designer,
            'ratingSummary' => Review::designerRatingSummary($designerId),
            'reviews' => $reviews,
        ]);
    }

    private function supplierForUser(Request $request): Supplier
    {
        $supplier = Supplier::query()->where('user_id', (int) $request->user()->id)->first();
        if (! $supplier) {
            abort(404);
        }

        return $supplier;
    }

    /**
     * Дизайнеры, с которыми у поставщика есть отправленные заказы.
     *
     * @return list<int>
     */
    private function designerIdsForSupplier(Supplier $supplier): array
    {
        return Supplier_orders::query()
            ->where('supplier_id', (int) $supplier->id)
            ->where('is_sent_to_supplier', true)
            ->distinct()
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}
