<?php

namespace App\Enums;

enum AssignedTeam: string
{
    case Programmer = 'programmer';
    case Network = 'network';
    case Hardware = 'hardware';

    public function label(): string
    {
        return match ($this) {
            self::Programmer => 'Software Engineer',
            self::Network => 'Network Engineer',
            self::Hardware => 'Network Engineer',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
