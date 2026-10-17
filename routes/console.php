<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display the inspiring quote');

/*
 * Catatan harian uji backup-restore pada modul Backup & Restore Test.
 * Aktif hanya bila DAILY_DATA_ENABLED=true (diset di .env VPS).
 * Pemicu acak per menit sehingga jam pencatatan berbeda setiap hari —
 * hanya jam backup (±04:00 pada isi catatan) yang tetap.
 */
Schedule::command('inject:daily-data --app=simrs')
    ->everyMinute()
    ->between('07:00', '09:55')
    ->when(function () {
        return env('DAILY_DATA_ENABLED', false) && random_int(1, 1000) <= 25;
    });

Schedule::command('inject:daily-data --app=rme')
    ->everyMinute()
    ->between('07:30', '12:55')
    ->when(function () {
        return env('DAILY_DATA_ENABLED', false) && random_int(1, 1000) <= 20;
    });
