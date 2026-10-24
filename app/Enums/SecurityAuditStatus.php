<?php

namespace App\Enums;

enum SecurityAuditStatus: string
{
    case Planned = 'planned';
    case Ongoing = 'ongoing';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Direncanakan',
            self::Ongoing => 'Berjalan',
            self::Completed => 'Selesai',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
