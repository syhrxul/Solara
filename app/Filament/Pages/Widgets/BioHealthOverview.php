<?php

namespace App\Filament\Pages\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class BioHealthOverview extends BaseWidget
{
    public ?array $weather = null;
    public ?array $skinWarning = null;
    public ?array $sleepCorrelation = null;

    protected function getStats(): array
    {
        if (!$this->weather || !$this->skinWarning || !$this->sleepCorrelation) {
            return [];
        }

        $temp = $this->weather['temperature'] ?? '--';
        $location = $this->weather['location'] ?? 'Sleman';
        $humidity = $this->weather['humidity'] ?? '--';
        $uvIndex = $this->weather['uv_index'] ?? 0;

        // Deskripsi Cuaca sederhana
        $code = $this->weather['weather_code'] ?? -1;
        $weatherDesc = 'Cerah';
        if (in_array($code, [51,53,55,61,63,65,80,81])) { $weatherDesc = 'Hujan'; }
        if (in_array($code, [95,96,99])) { $weatherDesc = 'Badai Petir'; }
        if (in_array($code, [2,3,45,48])) { $weatherDesc = 'Berawan'; }

        // Clean up text
        $skinDesc = \Illuminate\Support\Str::limit($this->skinWarning['message'] ?? '', 45, '...');
        $sleepDesc = \Illuminate\Support\Str::limit($this->sleepCorrelation['message'] ?? '', 45, '...');

        return [
            Stat::make('Cuaca Hari Ini', "{$temp}°C")
                ->description("{$weatherDesc} (UV: {$uvIndex}) - {$location}")
                ->color('info')
                ->icon('heroicon-o-cloud'),
                
            Stat::make('Status Kulit & Fisik', $this->skinWarning['title'] ?? 'Aman')
                ->description($skinDesc)
                ->color($this->skinWarning['color'] ?? 'success')
                ->icon('heroicon-o-face-smile'),
                
            Stat::make('Kualitas Tidur & Energi', $this->sleepCorrelation['title'] ?? 'Baik')
                ->description($sleepDesc)
                ->color($this->sleepCorrelation['color'] ?? 'warning')
                ->icon('heroicon-o-moon'),
        ];
    }
}
