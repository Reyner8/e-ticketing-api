<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display the inspiring quote');

/*
 * Data harian uji backup-restore.
 * Aktif hanya bila DAILY_DATA_ENABLED=true (diset di .env VPS).
 * Eksekusi sengaja acak per menit sehingga pola jam data berbeda setiap
 * hari — hanya jam backup (±04:00 pada catatan RST) yang tetap.
 */
Schedule::command('inject:daily-data --rst')
    ->everyMinute()
    ->between('07:00', '09:55')
    ->when(function () {
        return env('DAILY_DATA_ENABLED', false) && random_int(1, 1000) <= 25;
    });

Schedule::command('inject:daily-data --activity')
    ->everyMinute()
    ->between('07:30', '17:55')
    ->when(function () {
        return env('DAILY_DATA_ENABLED', false) && random_int(1, 1000) <= 5;
    });
