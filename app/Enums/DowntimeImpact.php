<?php

namespace App\Enums;

enum DowntimeImpact: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low Impact',
            self::Medium => 'Medium Impact',
            self::High => 'High Impact',
            self::Critical => 'Critical Impact',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
