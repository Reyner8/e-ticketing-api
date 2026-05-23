<?php

namespace App\Enums;

enum DowntimeStatus: string
{
    case Ongoing = 'ongoing';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Ongoing => 'Ongoing',
            self::Resolved => 'Resolved',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
