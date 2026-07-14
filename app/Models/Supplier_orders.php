<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Supplier_orders extends Model
{
    use SoftDeletes;

    protected $table = 'supplier_orders';

    protected $fillable = [
        'user_id',
        'project_id',
        'included_step_ids',
        'supplier_id',
        'status',
        'is_sent_to_supplier',
        'summa',
        'category',
        'mark',
        'room',
        'date_planned',
        'date_actual',
        'prepayment_date',
        'payment_date',
        'prepayment_amount',
        'payment_amount',
        'links',
        'files',
        'comment',
        'product_items',
        'bonus_percent',
        'offer_status',
        'offer_message',
        'offer_history',
    ];

    public const OFFER_PENDING_SUPPLIER = 'pending_supplier';

    public const OFFER_PENDING_DESIGNER = 'pending_designer';

    public const OFFER_ACCEPTED = 'accepted';

    public const OFFER_REJECTED = 'rejected';

    protected $casts = [
        'is_sent_to_supplier' => 'boolean',
        'included_step_ids' => 'array',
        'links' => 'array',
        'files' => 'array',
        'product_items' => 'array',
        'bonus_percent' => 'decimal:2',
        'offer_history' => 'array',
        'date_planned' => 'date',
        'date_actual' => 'date',
        'prepayment_date' => 'date',
        'payment_date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function designer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Эффективный статус оффера: старые отправленные заказы = accepted. */
    public function effectiveOfferStatus(): ?string
    {
        if ($this->offer_status) {
            return (string) $this->offer_status;
        }

        if ((bool) $this->is_sent_to_supplier) {
            return self::OFFER_ACCEPTED;
        }

        return null;
    }

    /** Активная поставка в воронке (принята или legacy без offer_status). */
    public function isInFunnel(): bool
    {
        if (! (bool) $this->is_sent_to_supplier) {
            return false;
        }

        return $this->effectiveOfferStatus() === self::OFFER_ACCEPTED;
    }

    public function isOfferNegotiation(): bool
    {
        return in_array($this->effectiveOfferStatus(), [
            self::OFFER_PENDING_SUPPLIER,
            self::OFFER_PENDING_DESIGNER,
            self::OFFER_REJECTED,
        ], true);
    }

    public function canSupplierRespondToOffer(): bool
    {
        return $this->effectiveOfferStatus() === self::OFFER_PENDING_SUPPLIER;
    }

    public function canDesignerRespondToOffer(): bool
    {
        return $this->effectiveOfferStatus() === self::OFFER_PENDING_DESIGNER;
    }

    public function bonusAmount(): ?int
    {
        if ($this->bonus_percent === null) {
            return null;
        }

        return (int) round((int) $this->summa * (float) $this->bonus_percent / 100);
    }

    /**
     * @return list<array{by: string, percent: float|null, message: string|null, at: string}>
     */
    public function offerHistoryList(): array
    {
        return is_array($this->offer_history) ? array_values($this->offer_history) : [];
    }

    public function appendOfferHistory(string $by, ?float $percent, ?string $message = null): void
    {
        $history = $this->offerHistoryList();
        $history[] = [
            'by' => $by,
            'percent' => $percent,
            'message' => $message !== null && trim($message) !== '' ? trim($message) : null,
            'at' => now()->toIso8601String(),
        ];
        $this->offer_history = $history;
    }

    /** @return array<string, mixed> */
    public function offerPayload(string $viewer): array
    {
        $status = $this->effectiveOfferStatus();

        return [
            'offer_status' => $status,
            'offer_message' => (string) ($this->offer_message ?? ''),
            'offer_history' => $this->offerHistoryList(),
            'bonus_percent' => $this->bonus_percent !== null ? (float) $this->bonus_percent : null,
            'bonus_amount' => $this->bonusAmount(),
            'can_respond_to_offer' => $viewer === 'supplier'
                ? $this->canSupplierRespondToOffer()
                : $this->canDesignerRespondToOffer(),
            'is_offer_negotiation' => $this->isOfferNegotiation(),
            'is_in_funnel' => $this->isInFunnel(),
        ];
    }

    /** @return list<int> */
    public static function normalizeStepIds(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $value), fn (int $id) => $id > 0)));
    }

    /** Сколько из $stepIds реально принадлежат этапам этого проекта (один SQL). */
    public static function countStepsInProject(int $projectId, array $stepIds): int
    {
        $stepIds = self::normalizeStepIds($stepIds);
        if ($stepIds === []) {
            return 0;
        }

        return (int) DB::table('project_stages_steps as s')
            ->join('project_stages as st', 'st.id', '=', 's.project_stage_id')
            ->where('st.project_id', $projectId)
            ->whereIn('s.id', $stepIds)
            ->count('s.id');
    }

    /**
     * Шаги для JSON: один запрос JOIN по id из заказа и project_id.
     *
     * @return list<array<string, mixed>>
     */
    public function includedStepsPayload(): array
    {
        $ids = self::normalizeStepIds($this->included_step_ids);
        if ($ids === []) {
            return [];
        }

        $rows = self::queryStepRowsForProject($this->project_id, $ids)->keyBy('id');

        return self::orderStepPayloads($ids, $rows);
    }

    /**
     * То же для списка заказов — один SQL на все id, без N+1.
     *
     * @param  Collection<int, self>  $orders
     * @return array<int, list<array<string, mixed>>>
     */
    public static function includedStepsPayloadForMany(Collection $orders): array
    {
        /** @var array<int, list<array<string, mixed>>> $out */
        $out = [];
        foreach ($orders as $order) {
            $out[(int) $order->id] = [];
        }

        $allIds = $orders
            ->flatMap(fn (self $o) => self::normalizeStepIds($o->included_step_ids))
            ->unique()
            ->values()
            ->all();

        if ($allIds === []) {
            return $out;
        }

        $rows = self::queryStepRowsGlobal($allIds)->keyBy('id');

        foreach ($orders as $order) {
            $pid = (int) $order->project_id;
            $filtered = $rows
                ->filter(fn ($r) => (int) $r->project_id === $pid)
                ->keyBy('id');
            $out[(int) $order->id] = self::orderStepPayloads(
                self::normalizeStepIds($order->included_step_ids),
                $filtered
            );
        }

        return $out;
    }

    /**
     * @param  list<int>  $ids
     * @param  Collection<int|string, object>  $rowsById keyed by step id
     * @return list<array<string, mixed>>
     */
    private static function orderStepPayloads(array $ids, Collection $rowsById): array
    {
        $list = [];
        foreach ($ids as $id) {
            $r = $rowsById->get($id);
            if ($r === null) {
                continue;
            }
            $list[] = self::stepRowToPayload($r);
        }

        return $list;
    }

    private static function stepRowToPayload(object $r): array
    {
        $type = (string) ($r->stage_type ?? '');
        $labelKey = 'projects.stage_'.$type;
        $label = (string) __($labelKey);
        if ($label === $labelKey) {
            $label = $type;
        }

        return [
            'id' => (int) $r->id,
            'stage_id' => (int) $r->stage_id,
            'stage_type' => $type,
            'stage_type_label' => $label,
            'title' => (string) $r->title,
            'result_status' => (string) ($r->result_status ?? 'pending'),
            'result_comment' => $r->result_comment,
            'link' => $r->link,
        ];
    }

    /** @param  list<int>  $stepIds */
    private static function queryStepRowsForProject(int $projectId, array $stepIds): Collection
    {
        return DB::table('project_stages_steps as s')
            ->join('project_stages as st', 'st.id', '=', 's.project_stage_id')
            ->where('st.project_id', $projectId)
            ->whereIn('s.id', $stepIds)
            ->orderBy('st.order')
            ->orderBy('s.order')
            ->select([
                's.id',
                's.title',
                's.result_status',
                's.result_comment',
                's.link',
                'st.id as stage_id',
                'st.stage_type',
            ])
            ->get();
    }

    /** @param  list<int>  $stepIds */
    private static function queryStepRowsGlobal(array $stepIds): Collection
    {
        return DB::table('project_stages_steps as s')
            ->join('project_stages as st', 'st.id', '=', 's.project_stage_id')
            ->whereIn('s.id', $stepIds)
            ->orderBy('st.order')
            ->orderBy('s.order')
            ->select([
                's.id',
                's.title',
                's.result_status',
                's.result_comment',
                's.link',
                'st.id as stage_id',
                'st.stage_type',
                'st.project_id',
            ])
            ->get();
    }
}
