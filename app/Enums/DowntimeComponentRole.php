<?php

namespace App\Enums;

enum DowntimeComponentRole: string
{
    case Source = 'source';
    case Affected = 'affected';

    public function label(): string
    {
        return match ($this) {
            self::Source => 'Directly Down',
            self::Affected => 'Affected',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
