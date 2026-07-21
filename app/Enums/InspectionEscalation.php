<?php

namespace App\Enums;

enum InspectionEscalation: string
{
    case Ipsrs = 'ipsrs';
    case Director = 'director';

    public function label(): string
    {
        return match ($this) {
            self::Ipsrs => 'IPSRS',
            self::Director => 'Direktur',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
