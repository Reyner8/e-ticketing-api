<?php

namespace App\Enums;

enum DowntimeComponentCategory: string
{
    case Application = 'application';
    case Network = 'network';
    case Utility = 'utility';
    case Infrastructure = 'infrastructure';
    case Equipment = 'equipment';
    case OperationalService = 'operational_service';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Application => 'Application',
            self::Network => 'Network',
            self::Utility => 'Utility / Electricity',
            self::Infrastructure => 'Infrastructure',
            self::Equipment => 'Equipment',
            self::OperationalService => 'Operational Service',
            self::Other => 'Other',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
