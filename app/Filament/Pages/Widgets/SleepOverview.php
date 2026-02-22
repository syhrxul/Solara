<?php

namespace App\Filament\Pages\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\HealthMetric;

class SleepOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $metrics = HealthMetric::where('user_id', auth()->id())
            ->where('type', 'sleep')
            ->orderBy('date', 'desc')
            ->take(30)
            ->get();

        if ($metrics->isEmpty()) {
            return [
                Stat::make('Rata-rata Durasi', 'Belum ada data'),
                Stat::make('Rata-rata Mulai Tidur', 'Belum ada data'),
                Stat::make('Kesimpulan', '-'),
                Stat::make('Saran Tidur', '-'),
            ];
        }

        // Hitung total rata-rata PER HARI (bukan per sesi) untuk Kesimpulan yang akurat
        $dailyMetrics = [];
        foreach ($metrics as $metric) {
            $dateKey = $metric->date->toDateString();
            if (!isset($dailyMetrics[$dateKey])) {
                $dailyMetrics[$dateKey] = 0;
            }
            $dailyMetrics[$dateKey] += $metric->value;
        }

        $avgValue = count($dailyMetrics) > 0 ? array_sum($dailyMetrics) / count($dailyMetrics) : 0;
        $avgHours = floor($avgValue);
        $avgMinutes = round(($avgValue - $avgHours) * 60);

        $totalMinutes = 0;
        $count = 0;
        foreach ($metrics as $metric) {
            $timeBed = $metric->details['time_bed'] ?? null;
            if ($timeBed) {
                $pieces = explode(':', $timeBed);
                if (count($pieces) == 2) {
                    $h = (int) $pieces[0];
                    $m = (int) $pieces[1];
                    $mins = ($h * 60) + $m;
                    // Anggap batas hari jam 12 Siang untuk menghindari math error (jam 23:00 vs 01:00)
                    if ($h < 12) {
                        $mins += 24 * 60;
                    }
                    $totalMinutes += $mins;
                    $count++;
                }
            }
        }

        $avgBedTimeStr = '--:--';
        $avgH_real = 0;
        if ($count > 0) {
            $avgMins = $totalMinutes / $count;
            if ($avgMins >= (24 * 60)) {
                $avgMins -= (24 * 60);
            }
            $avgH = floor($avgMins / 60);
            $avgM = round($avgMins % 60);
            $avgH_real = $avgH;
            $avgBedTimeStr = sprintf('%02d:%02d', $avgH, $avgM);
        }

        // Tentukan Kesimpulan dan Saran
        $kesimpulan = 'Baik';
        $saran = 'Pertahankan pola saat ini.';
        $color = 'success';

        if ($avgValue < 6) {
            $kesimpulan = 'Kurang Tidur';
            $saran = 'Usahakan tidur 7-8 jam per hari, kurangi stres malam hari.';
            $color = 'danger';
        } elseif ($avgValue > 9) {
            $kesimpulan = 'Tidur Terlalu Lama';
            $saran = 'Kurangi jam tidur. Terlalu lama tidur membuat badan lemas.';
            $color = 'warning';
        } elseif ($avgH_real > 0 && $avgH_real < 5) {
            $kesimpulan = 'Sering Begadang';
            $saran = 'Tidur cukup, tapi tidur larut tidak baik buat hormon (coba max jam 12).';
            $color = 'warning';
        }

        return [
            Stat::make('Rata-rata Durasi', "{$avgHours}j {$avgMinutes}m")
                ->description('Durasi akumulasi per hari (Bulan Ini)')
                ->color('primary'),
            Stat::make('Rata-rata Jam Tidur', $avgBedTimeStr)
                ->description('Kebiasaan mulai terlelap')
                ->color('info'),
            Stat::make('Kesimpulan Tidur', $kesimpulan)
                ->description('Cek kualitas pola Anda')
                ->color($color),
            Stat::make('Saran Kesehatan', $saran)
                ->color($color),
        ];
    }
}
