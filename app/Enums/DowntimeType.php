<?php

namespace App\Enums;

enum DowntimeType: string
{
    case Planned = 'planned';
    case Unplanned = 'unplanned';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planned',
            self::Unplanned => 'Unplanned'
        };
    }

    public static function values(): array 
    {
        return array_column(self::cases(), 'value');
    }
}
