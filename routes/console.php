<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 🔔 Kirim notifikasi jadwal kuliah setiap pagi jam 06:00
Schedule::command('solara:notify-schedule')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->description('Notifikasi jadwal kuliah harian');
