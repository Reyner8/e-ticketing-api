<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Menggeser timestamp turunan (riwayat aktivitas/status) agar konsisten
 * dengan waktu acak pada data uji yang dibuat mundur (backfill).
 */
class Backdate
{
    public static function activityLogs(Model $model, Carbon $ts): void
    {
        DB::table('activity_logs')
            ->where('loggable_type', $model->getMorphClass())
            ->where('loggable_id', (string) $model->getKey())
            ->update(['performed_at' => $ts]);
    }

    public static function statusHistories(Model $model, Carbon $ts): void
    {
        DB::table('status_histories')
            ->where('statusable_type', $model->getMorphClass())
            ->where('statusable_id', (string) $model->getKey())
            ->update(['changed_at' => $ts, 'effective_at' => $ts]);
    }
}
