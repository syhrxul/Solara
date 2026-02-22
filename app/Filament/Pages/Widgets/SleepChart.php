<?php

namespace App\Filament\Pages\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\HealthMetric;
use Carbon\Carbon;

class SleepChart extends ChartWidget
{
    protected ?string $heading = 'Grafik Durasi Tidur (14 Hari Terakhir)';
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $metrics = HealthMetric::where('user_id', auth()->id())
            ->where('type', 'sleep')
            ->where('date', '>=', Carbon::now()->subDays(14)->toDateString())
            ->orderBy('date', 'asc')
            ->get();

        $dailyTotals = [];
        foreach ($metrics as $metric) {
            $date = Carbon::parse($metric->date)->translatedFormat('d M');
            if (!isset($dailyTotals[$date])) {
                $dailyTotals[$date] = 0;
            }
            $dailyTotals[$date] += $metric->value;
        }

        $labels = array_keys($dailyTotals);
        $data = array_map(fn($val) => round($val, 1), array_values($dailyTotals));

        return [
            'datasets' => [
                [
                    'label' => 'Total Durasi Tidur (Jam)',
                    'data' => $data,
                    'backgroundColor' => '#8b5cf6', // Indigo/Purple
                    'borderColor' => '#8b5cf6',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar'; // Kita gunakan grafik batang agar keren
    }
}
