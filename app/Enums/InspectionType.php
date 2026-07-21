<?php

namespace App\Enums;

enum InspectionType: string
{
    case Weekly = 'weekly';
    case Incidental = 'incidental';

    public function label(): string
    {
        return match ($this) {
            self::Weekly => 'Mingguan',
            self::Incidental => 'Insidental',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
