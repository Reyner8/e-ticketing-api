<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SequentialIdGenerator
{
    /**
     * Generate PREFIX-YYYY-NNN using a locked max lookup.
     *
     * @param  class-string<Model>  $modelClass
     */
    public static function next(string $modelClass, string $prefix): string
    {
        return DB::transaction(function () use ($modelClass, $prefix) {
            $year = now()->format('Y');

            $last = $modelClass::query()
                ->where('id', 'like', "{$prefix}-{$year}-%")
                ->lockForUpdate()
                ->orderBy('id', 'desc')
                ->first();

            $lastNumber = $last ? (int) substr($last->id, -3) : 0;
            $newNumber = str_pad((string) ($lastNumber + 1), 3, '0', STR_PAD_LEFT);

            return "{$prefix}-{$year}-{$newNumber}";
        });
    }
}
