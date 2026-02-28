<?php

namespace App\Filament\Pages\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class BioHealthDailyCuaca extends BaseWidget
{
    public ?array $weather = null;
    public ?string $hijriDate = '-';

    public function mount(?array $weather = null)
    {
        $this->weather = $weather;

        // Ambil Kalender Hijriah
        try {
            $lat = $this->weather['lat'] ?? -7.71556;
            $lng = $this->weather['lng'] ?? 110.35556;
            $dateStr = Carbon::now('Asia/Jakarta')->format('d-m-Y');
            $prayerResponse = \Illuminate\Support\Facades\Http::get("https://api.aladhan.com/v1/timings/$dateStr", [
                'latitude' => $lat,
                'longitude' => $lng,
                'method' => 20
            ]);

            if ($prayerResponse->successful()) {
                $hijri = $prayerResponse->json('data.date.hijri');
                $this->hijriDate = "{$hijri['day']} {$hijri['month']['en']} {$hijri['year']} H";
            }
        } catch (\Exception $e) {
            $this->hijriDate = '-';
        }
    }

    protected function getStats(): array
    {
        if (!$this->weather) {
            return [];
        }

        $temp = $this->weather['temperature'] ?? '--';
        $location = $this->weather['location'] ?? 'Sleman';
        $code = $this->weather['weather_code'] ?? -1;
        $hum = $this->weather['humidity'] ?? '--';

        // Deskripsi Cuaca sederhana
        $weatherDesc = 'Cerah';
        if (in_array($code, [51,53,55,61,63,65,80,81])) { $weatherDesc = 'Hujan'; }
        if (in_array($code, [95,96,99])) { $weatherDesc = 'Badai Petir'; }
        if (in_array($code, [2,3,45,48])) { $weatherDesc = 'Berawan'; }

        return [
            Stat::make('Cuaca Hari Ini', "{$temp}°C")
                ->description("{$weatherDesc} (Klp {$hum}%) - {$location}")
                ->color('info')
                ->icon('heroicon-o-cloud'),
                
            Stat::make('Kalender Islam', $this->hijriDate)
                ->description(Carbon::now('Asia/Jakarta')->translatedFormat('l, d F Y'))
                ->color('success')
                ->icon('heroicon-o-calendar-days'),
                
            Stat::make('Wilayah Pantauan', "Lokasi: {$location}")
                ->description("Sumber: BMKG / OpenMeteo")
                ->color('warning')
                ->icon('heroicon-o-map-pin'),
        ];
    }
}
