<?php

namespace App\Enums;

enum Priorities: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low Priority',
            self::Medium => 'Medium Priority',
            self::High => 'High Priority',
            self::Critical => 'Critical Priority',
        };
    }

    public function slaHours(): int
    {
        return match ($this) {
            self::Critical => 4,
            self::High => 8,
            self::Medium => 24,
            self::Low => 72
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
