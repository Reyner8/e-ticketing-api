<?php

namespace App\Enums;

enum TargetApplication: string
{
    case SIMRS = 'simrs';
    case RME = 'rme';
    case Antrean = 'antrean';
    case Lainnya = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::SIMRS => 'SIMRS',
            self::RME => 'RME',
            self::Antrean => 'ANTREAN',
            self::Lainnya => 'Lainnya',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
