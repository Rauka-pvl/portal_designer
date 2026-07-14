<?php

namespace App\Support;

use App\Models\DesignerCashbackTransaction;
use App\Models\Supplier_orders;
use Illuminate\Support\Facades\DB;

class CashbackAccrual
{
    public static function forCompletedOrder(Supplier_orders $order): void
    {
        if ((string) $order->status !== 'delivery_completed') {
            return;
        }

        if (! $order->isInFunnel()) {
            return;
        }

        $amount = $order->bonusAmount();
        if ($amount === null || $amount <= 0) {
            return;
        }

        $designerId = (int) $order->user_id;
        if ($designerId < 1) {
            return;
        }

        DB::transaction(function () use ($order, $amount, $designerId) {
            $exists = DesignerCashbackTransaction::query()
                ->where('supplier_order_id', $order->id)
                ->where('type', DesignerCashbackTransaction::TYPE_ACCRUAL)
                ->lockForUpdate()
                ->exists();

            if ($exists) {
                return;
            }

            $order->loadMissing('supplier:id,name');

            $supplierName = trim((string) ($order->supplier->name ?? ''));
            $description = $supplierName !== ''
                ? __('cashback.accrual_description', ['supplier' => $supplierName])
                : __('cashback.accrual_description_short');

            DesignerCashbackTransaction::create([
                'user_id' => $designerId,
                'type' => DesignerCashbackTransaction::TYPE_ACCRUAL,
                'amount' => $amount,
                'supplier_order_id' => $order->id,
                'status' => DesignerCashbackTransaction::STATUS_COMPLETED,
                'description' => $description,
                'meta' => [
                    'bonus_percent' => $order->bonus_percent !== null ? (float) $order->bonus_percent : null,
                    'order_summa' => (int) $order->summa,
                ],
            ]);
        });
    }
}
