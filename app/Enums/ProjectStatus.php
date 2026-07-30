<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case ContractNegotiation = 'contract_negotiation';
    case ContractSigned = 'contract_signed';
    case PrepaymentReceived = 'prepayment_received';
    case TzSigned = 'tz_signed';
    case DocumentsSigned = 'documents_signed';
    case InWork = 'in_work';

    public function label(): string
    {
        return __('projects.status_'.$this->value);
    }

    public function defaultColor(): string
    {
        return match ($this) {
            self::ContractNegotiation => '#a855f7',
            self::ContractSigned => '#3b82f6',
            self::PrepaymentReceived => '#22c55e',
            self::TzSigned => '#6366f1',
            self::DocumentsSigned => '#06b6d4',
            self::InWork => '#eab308',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Default funnel order for seeding.
     *
     * @return list<self>
     */
    public static function funnelOrder(): array
    {
        return self::cases();
    }
}
