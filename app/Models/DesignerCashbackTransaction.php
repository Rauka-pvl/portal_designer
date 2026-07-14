<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DesignerCashbackTransaction extends Model
{
    public const TYPE_ACCRUAL = 'accrual';

    public const TYPE_WITHDRAWAL = 'withdrawal';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_PENDING = 'pending';

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'supplier_order_id',
        'status',
        'description',
        'meta',
    ];

    protected $casts = [
        'amount' => 'integer',
        'supplier_order_id' => 'integer',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Supplier_orders::class, 'supplier_order_id');
    }

    public function isCredit(): bool
    {
        return $this->type === self::TYPE_ACCRUAL;
    }

    public static function availableBalance(int $userId): int
    {
        $row = self::query()
            ->where('user_id', $userId)
            ->where('status', self::STATUS_COMPLETED)
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN type = ? THEN amount ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN type = ? THEN amount ELSE 0 END), 0) AS balance',
                [self::TYPE_ACCRUAL, self::TYPE_WITHDRAWAL]
            )
            ->first();

        return max(0, (int) ($row->balance ?? 0));
    }

    public static function totalEarned(int $userId): int
    {
        return (int) self::query()
            ->where('user_id', $userId)
            ->where('type', self::TYPE_ACCRUAL)
            ->where('status', self::STATUS_COMPLETED)
            ->sum('amount');
    }

    public static function totalWithdrawn(int $userId): int
    {
        return (int) self::query()
            ->where('user_id', $userId)
            ->where('type', self::TYPE_WITHDRAWAL)
            ->where('status', self::STATUS_COMPLETED)
            ->sum('amount');
    }

    public static function earnedInPeriod(int $userId, Carbon $from): int
    {
        return (int) self::query()
            ->where('user_id', $userId)
            ->where('type', self::TYPE_ACCRUAL)
            ->where('status', self::STATUS_COMPLETED)
            ->where('created_at', '>=', $from)
            ->sum('amount');
    }

    /**
     * @return list<array{date: string, amount: int}>
     */
    public static function dailyAccruals(int $userId, int $days): array
    {
        $from = now()->subDays($days - 1)->startOfDay();

        $rows = self::query()
            ->where('user_id', $userId)
            ->where('type', self::TYPE_ACCRUAL)
            ->where('status', self::STATUS_COMPLETED)
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) AS day, SUM(amount) AS total')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('day')
            ->get();

        $map = $rows->pluck('total', 'day')->map(fn ($v) => (int) $v)->all();
        $result = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $from->copy()->addDays($i)->toDateString();
            $result[] = [
                'date' => $date,
                'amount' => $map[$date] ?? 0,
            ];
        }

        return $result;
    }
}
