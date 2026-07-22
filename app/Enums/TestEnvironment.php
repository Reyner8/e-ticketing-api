<?php

namespace App\Enums;

enum TestEnvironment: string
{
    case LocalDevelopment = 'local_development';
    case ServerStaging = 'server_staging';

    public function label(): string
    {
        return match ($this) {
            self::LocalDevelopment => 'Local Development',
            self::ServerStaging => 'Server Staging',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
