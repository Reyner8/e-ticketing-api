<?php

namespace App\Enums;

enum VisitorStatus: string
{
    case Inside = 'inside';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Inside => 'Di dalam',
            self::Completed => 'Selesai',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
