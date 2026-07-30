<?php

namespace App\Enums;

enum SupplyStatus: string
{
    case Draft = 'draft';
    case OrderCreated = 'order_created';
    case OrderConfirmed = 'order_confirmed';
    case AdvancePayment = 'advance_payment';
    case FullPayment = 'full_payment';
    case DeliveryCompleted = 'delivery_completed';

    public function label(): string
    {
        return __('supplier-orders.status_'.$this->value);
    }

    public function defaultColor(): string
    {
        return match ($this) {
            self::Draft => '#64748b',
            self::OrderCreated => '#3b82f6',
            self::OrderConfirmed => '#22c55e',
            self::AdvancePayment => '#a855f7',
            self::FullPayment => '#6366f1',
            self::DeliveryCompleted => '#06b6d4',
        };
    }

    public function isFunnelColumn(): bool
    {
        return $this !== self::Draft;
    }

    public function marksBalancePaymentDone(): bool
    {
        return in_array($this, [self::FullPayment, self::DeliveryCompleted], true);
    }

    public function marksPrepaymentDone(): bool
    {
        return in_array($this, [self::AdvancePayment, self::FullPayment, self::DeliveryCompleted], true);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return list<self>
     */
    public static function funnelOrder(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $status) => $status->isFunnelColumn()
        ));
    }
}
