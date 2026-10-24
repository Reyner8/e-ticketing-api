<?php

namespace App\Enums;

enum FindingSeverity: string
{
    case Low = 'rendah';
    case Medium = 'sedang';
    case High = 'tinggi';
    case Critical = 'kritis';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Rendah',
            self::Medium => 'Sedang',
            self::High => 'Tinggi',
            self::Critical => 'Kritis',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
