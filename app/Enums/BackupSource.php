<?php

namespace App\Enums;

enum BackupSource: string
{
    case Nas = 'nas';
    case Hdd = 'hdd';
    case Pc = 'pc';
    case Server = 'server';

    public function label(): string
    {
        return match ($this) {
            self::Nas => 'NAS',
            self::Hdd => 'HDD',
            self::Pc => 'PC',
            self::Server => 'Server',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
