<?php

namespace App\Enums;

enum RestoreType: string
{
    case Database = 'database';
    case Application = 'application';
    case Both = 'both';

    public function label(): string
    {
        return match ($this) {
            self::Database => 'Database',
            self::Application => 'Aplikasi',
            self::Both => 'Keduanya',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
