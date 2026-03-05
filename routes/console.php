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

// 🔔 Pengecekan reminder_time habit setiap 1 menit (sesuai jam yang diset user)
Schedule::command('solara:notify-habits')
    ->everyMinute()
    ->withoutOverlapping()
    ->description('Pengingat Habit harian');

// 🔔 Pengingat tugas kuliah dan tasks biasa (H-1 & Hari H) jam 07:00
Schedule::command('solara:notify-tasks')
    ->dailyAt('04:00')
    ->withoutOverlapping()
    ->description('Pengingat batas waktu tugas/tasks');

// 💔 Cek streak habit yang putus (setiap hari jam 00:05)
Schedule::command('solara:check-habit-streaks')
    ->dailyAt('00:05')
    ->withoutOverlapping()
    ->description('Cek dan putuskan streak habit yang tidak dikerjakan kemarin');

// 🔄 Tarik data kesehatan dari Google Fit setiap 3 jam
Schedule::command('health:sync')
    ->everyThreeHours()
    ->withoutOverlapping()
    ->description('Sinkronisasi data Google Fit');
