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
    ];

    protected $casts = [
        'is_sent_to_supplier' => 'boolean',
        'included_step_ids' => 'array',
        'links' => 'array',
        'files' => 'array',
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
