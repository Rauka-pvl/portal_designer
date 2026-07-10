<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Supplier;
use App\Models\Supplier_orders;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    /**
     * Отзывы, написанные о текущем пользователе (для чтения на странице профиля).
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $isSupplier = ($user->role ?? '') === 'supplier';

        if ($isSupplier) {
            $supplier = Supplier::query()->where('user_id', (int) $user->id)->first();

            $reviews = Review::query()
                ->where('direction', Review::DIRECTION_DESIGNER_TO_SUPPLIER)
                ->when($supplier, fn ($q) => $q->where('supplier_id', (int) $supplier->id), fn ($q) => $q->whereRaw('1 = 0'))
                ->with(['reviewer:id,name'])
                ->orderByDesc('id')
                ->paginate(10)
                ->withQueryString();

            return view('reviews.index', [
                'reviews' => $reviews,
                'layout' => 'layouts.supplier',
                'isSupplier' => true,
                'averageRating' => $this->averageRating(Review::DIRECTION_DESIGNER_TO_SUPPLIER, 'supplier_id', $supplier?->id),
                'totalReviews' => $this->totalReviews(Review::DIRECTION_DESIGNER_TO_SUPPLIER, 'supplier_id', $supplier?->id),
                'profileRoute' => 'supplier.profile.show',
                'reviewsRoute' => 'supplier.profile.reviews',
            ]);
        }

        $reviews = Review::query()
            ->where('direction', Review::DIRECTION_SUPPLIER_TO_DESIGNER)
            ->where('designer_user_id', (int) $user->id)
            ->with(['reviewer:id,name', 'supplier:id,name'])
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('reviews.index', [
            'reviews' => $reviews,
            'layout' => 'layouts.dashboard',
            'isSupplier' => false,
            'averageRating' => $this->averageRating(Review::DIRECTION_SUPPLIER_TO_DESIGNER, 'designer_user_id', $user->id),
            'totalReviews' => $this->totalReviews(Review::DIRECTION_SUPPLIER_TO_DESIGNER, 'designer_user_id', $user->id),
            'profileRoute' => 'profile.show',
            'reviewsRoute' => 'profile.reviews',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $role = (string) ($user->role ?? '');

        $data = $request->validate([
            'order_id' => ['required', 'integer', 'exists:supplier_orders,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $order = Supplier_orders::query()->with('supplier:id,user_id,name')->findOrFail((int) $data['order_id']);

        if ((string) $order->status !== 'delivery_completed') {
            return redirect()->back()->with('status', __('reviews.not_completed'));
        }

        if ($role === 'supplier') {
            $supplier = Supplier::query()->where('user_id', (int) $user->id)->first();
            if (! $supplier || (int) $order->supplier_id !== (int) $supplier->id) {
                abort(403);
            }

            $direction = Review::DIRECTION_SUPPLIER_TO_DESIGNER;
            $designerUserId = (int) $order->user_id;
            $supplierId = (int) $supplier->id;
        } elseif ($role === 'designer') {
            if ((int) $order->user_id !== (int) $user->id) {
                abort(403);
            }

            $direction = Review::DIRECTION_DESIGNER_TO_SUPPLIER;
            $designerUserId = (int) $user->id;
            $supplierId = (int) $order->supplier_id;
        } else {
            abort(403);
        }

        Review::query()->updateOrCreate(
            [
                'supplier_order_id' => (int) $order->id,
                'direction' => $direction,
            ],
            [
                'reviewer_user_id' => (int) $user->id,
                'designer_user_id' => $designerUserId,
                'supplier_id' => $supplierId,
                'rating' => (int) $data['rating'],
                'comment' => $data['comment'] ?? null,
            ]
        );

        $actionKey = $role === 'supplier' ? 'rate_designer' : 'rate_supplier';
        UserNotification::query()
            ->where('user_id', (int) $user->id)
            ->where('action_key', $actionKey)
            ->where('related_order_id', (int) $order->id)
            ->update([
                'is_read' => true,
                'read_at' => now(),
                'action_key' => null,
            ]);

        return redirect()->back()->with('status', __('reviews.thanks'));
    }

    private function totalReviews(string $direction, string $column, $value): int
    {
        if ($value === null) {
            return 0;
        }

        return (int) Review::query()
            ->where('direction', $direction)
            ->where($column, (int) $value)
            ->count();
    }

    private function averageRating(string $direction, string $column, $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $avg = Review::query()
            ->where('direction', $direction)
            ->where($column, (int) $value)
            ->avg('rating');

        return $avg !== null ? round((float) $avg, 1) : null;
    }
}
