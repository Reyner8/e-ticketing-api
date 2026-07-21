<?php

namespace App\Enums;

enum RestoreTestResult: string
{
    case Success = 'success';
    case Failed = 'failed';
    case SuccessWithNotes = 'success_with_notes';

    public function label(): string
    {
        return match ($this) {
            self::Success => 'Sukses',
            self::Failed => 'Gagal',
            self::SuccessWithNotes => 'Sukses + catatan',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
