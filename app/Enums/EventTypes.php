<?php

namespace App\Enums;

enum EventTypes: string
{
    case PlannedDowntime = 'planned_downtime';
    case Maintenance = 'maintenance';
    case Deadline = 'deadline';

    public function label(): string
    {
        return match ($this) {
            self::PlannedDowntime => 'Planned Downtime',
            self::Maintenance => 'Maintenance',
            self::Deadline => 'Deadline'
        };
    }

    public function defaultColor(): string
    {
        return match ($this) {
            self::PlannedDowntime => '#EF4444',
            self::Maintenance => '#F59E0B',
            self::Deadline => '#3B82F6',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
