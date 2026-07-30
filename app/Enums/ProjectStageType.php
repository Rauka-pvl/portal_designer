<?php

namespace App\Enums;

enum ProjectStageType: string
{
    case Measurement = 'measurement';
    case Planning = 'planning';
    case Drawings = 'drawings';
    case Equipment = 'equipment';
    case Estimate = 'estimate';
    case Visualization = 'visualization';

    public function label(): string
    {
        return __('projects.stage_'.$this->value);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
