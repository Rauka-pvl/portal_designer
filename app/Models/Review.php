<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    public const DIRECTION_DESIGNER_TO_SUPPLIER = 'designer_to_supplier';
    public const DIRECTION_SUPPLIER_TO_DESIGNER = 'supplier_to_designer';

    protected $table = 'reviews';

    protected $fillable = [
        'supplier_order_id',
        'direction',
        'reviewer_user_id',
        'designer_user_id',
        'supplier_id',
        'rating',
        'comment',
    ];

    protected $casts = [
        'supplier_order_id' => 'integer',
        'reviewer_user_id' => 'integer',
        'designer_user_id' => 'integer',
        'supplier_id' => 'integer',
        'rating' => 'integer',
    ];

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }

    public function designer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'designer_user_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Supplier_orders::class, 'supplier_order_id');
    }

    /**
     * Сводка рейтинга (среднее + количество) по одному поставщику.
     *
     * @return array{average: float|null, count: int}
     */
    public static function supplierRatingSummary(int $supplierId): array
    {
        return self::ratingSummaries(self::DIRECTION_DESIGNER_TO_SUPPLIER, 'supplier_id', [$supplierId])[$supplierId]
            ?? ['average' => null, 'count' => 0];
    }

    /**
     * Сводка рейтинга по одному дизайнеру.
     *
     * @return array{average: float|null, count: int}
     */
    public static function designerRatingSummary(int $designerUserId): array
    {
        return self::ratingSummaries(self::DIRECTION_SUPPLIER_TO_DESIGNER, 'designer_user_id', [$designerUserId])[$designerUserId]
            ?? ['average' => null, 'count' => 0];
    }

    /**
     * Пакетная сводка рейтинга по нескольким поставщикам (без N+1).
     *
     * @param  list<int>  $supplierIds
     * @return array<int, array{average: float|null, count: int}>
     */
    public static function supplierRatingSummaries(array $supplierIds): array
    {
        return self::ratingSummaries(self::DIRECTION_DESIGNER_TO_SUPPLIER, 'supplier_id', $supplierIds);
    }

    /**
     * Пакетная сводка рейтинга по нескольким дизайнерам (без N+1).
     *
     * @param  list<int>  $designerUserIds
     * @return array<int, array{average: float|null, count: int}>
     */
    public static function designerRatingSummaries(array $designerUserIds): array
    {
        return self::ratingSummaries(self::DIRECTION_SUPPLIER_TO_DESIGNER, 'designer_user_id', $designerUserIds);
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, array{average: float|null, count: int}>
     */
    private static function ratingSummaries(string $direction, string $column, array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn ($v) => $v > 0)));
        if ($ids === []) {
            return [];
        }

        return self::query()
            ->where('direction', $direction)
            ->whereIn($column, $ids)
            ->groupBy($column)
            ->selectRaw("$column as ref_id, AVG(rating) as avg_rating, COUNT(*) as cnt")
            ->get()
            ->mapWithKeys(fn ($row) => [
                (int) $row->ref_id => [
                    'average' => $row->avg_rating !== null ? round((float) $row->avg_rating, 1) : null,
                    'count' => (int) $row->cnt,
                ],
            ])
            ->all();
    }

    /**
     * Последние отзывы о поставщике (для превью в модалке).
     */
    public static function recentForSupplier(int $supplierId, int $limit = 5)
    {
        return self::query()
            ->where('direction', self::DIRECTION_DESIGNER_TO_SUPPLIER)
            ->where('supplier_id', $supplierId)
            ->with('reviewer:id,name')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Последние отзывы о дизайнере.
     */
    public static function recentForDesigner(int $designerUserId, int $limit = 20)
    {
        return self::query()
            ->where('direction', self::DIRECTION_SUPPLIER_TO_DESIGNER)
            ->where('designer_user_id', $designerUserId)
            ->with('reviewer:id,name')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Создаёт уведомления с просьбой оценить обе стороны после завершения поставки.
     */
    public static function requestReviewsForCompletedOrder(Supplier_orders $order): void
    {
        if ((string) $order->status !== 'delivery_completed') {
            return;
        }

        $supplier = $order->relationLoaded('supplier') ? $order->supplier : $order->supplier()->first();
        if (! $supplier) {
            return;
        }

        $designerUserId = (int) $order->user_id;
        $supplierId = (int) $order->supplier_id;
        $supplierUserId = (int) ($supplier->user_id ?? 0);

        // Просьба к дизайнеру оценить поставщика
        if ($designerUserId > 0) {
            self::createReviewRequest(
                userId: $designerUserId,
                orderId: (int) $order->id,
                supplierId: $supplierId,
                direction: self::DIRECTION_DESIGNER_TO_SUPPLIER,
                actionKey: 'rate_supplier',
                title: __('notifications.rate_supplier_title'),
                comment: __('notifications.rate_supplier_comment', [
                    'order' => (string) $order->id,
                    'supplier' => (string) ($supplier->name ?? ''),
                ]),
            );
        }

        // Просьба к поставщику оценить дизайнера
        if ($supplierUserId > 0) {
            $designer = $order->relationLoaded('designer') ? $order->designer : $order->designer()->first();
            self::createReviewRequest(
                userId: $supplierUserId,
                orderId: (int) $order->id,
                supplierId: $supplierId,
                direction: self::DIRECTION_SUPPLIER_TO_DESIGNER,
                actionKey: 'rate_designer',
                title: __('notifications.rate_designer_title'),
                comment: __('notifications.rate_designer_comment', [
                    'order' => (string) $order->id,
                    'designer' => (string) ($designer->name ?? ''),
                ]),
            );
        }
    }

    private static function createReviewRequest(
        int $userId,
        int $orderId,
        int $supplierId,
        string $direction,
        string $actionKey,
        string $title,
        string $comment,
    ): void {
        // Уже оценено — не просим повторно.
        $alreadyReviewed = self::query()
            ->where('supplier_order_id', $orderId)
            ->where('direction', $direction)
            ->exists();
        if ($alreadyReviewed) {
            return;
        }

        // Уведомление по этой поставке уже создано.
        $alreadyNotified = UserNotification::query()
            ->where('user_id', $userId)
            ->where('action_key', $actionKey)
            ->where('related_order_id', $orderId)
            ->exists();
        if ($alreadyNotified) {
            return;
        }

        UserNotification::query()->create([
            'user_id' => $userId,
            'title' => $title,
            'comment' => $comment,
            'is_read' => false,
            'related_supplier_id' => $supplierId,
            'related_order_id' => $orderId,
            'action_key' => $actionKey,
        ]);
    }
}
