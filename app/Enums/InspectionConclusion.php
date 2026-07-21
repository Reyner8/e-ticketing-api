<?php

namespace App\Enums;

enum InspectionConclusion: string
{
    case Safe = 'safe';
    case Findings = 'findings';

    public function label(): string
    {
        return match ($this) {
            self::Safe => 'Aman',
            self::Findings => 'Ada temuan',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
