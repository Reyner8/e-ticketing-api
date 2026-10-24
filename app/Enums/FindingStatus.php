<?php

namespace App\Enums;

enum FindingStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Buka',
            self::InProgress => 'Diproses',
            self::Completed => 'Selesai',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
