<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\On;

class JadwalOverview extends BaseWidget
{
    public array $activeLocation = [];
    public $weatherData = [];
    public $hijriDate = '';

    public function mount()
    {
        $settings = auth()->user()->settings ?? [];
        $this->activeLocation = $settings['jadwal_location'] ?? [
            'name' => 'Sleman',
            'lat'  => -7.71556,
            'lng'  => 110.35556,
        ];
    }

    #[On('location-changed')]
    public function updateLocation()
    {
        // Reload from settings to get the newest selected location
        $settings = auth()->user()->settings ?? [];
        $this->activeLocation = $settings['jadwal_location'] ?? [
            'name' => 'Sleman',
            'lat'  => -7.71556,
            'lng'  => 110.35556,
        ];
        
        // Reload data
        $this->getData();
    }

    protected function getData()
    {
        $lat = $this->activeLocation['lat'] ?? -7.71556;
        $lng = $this->activeLocation['lng'] ?? 110.35556;

        // 2. Ambil Info Cuaca
        try {
            $weatherResponse = Http::get("https://api.open-meteo.com/v1/forecast", [
                'latitude' => $lat,
                'longitude' => $lng,
                'current' => 'temperature_2m,relative_humidity_2m,weather_code',
                'timezone' => 'Asia/Jakarta'
            ]);

            if ($weatherResponse->successful()) {
                $this->weatherData = $weatherResponse->json('current');
            }
        } catch (\Exception $e) {
            $this->weatherData = [];
        }

        // 3. Ambil API Kalender Hijriah
        try {
            $dateStr = Carbon::now('Asia/Jakarta')->format('d-m-Y');
            $prayerResponse = Http::get("https://api.aladhan.com/v1/timings/$dateStr", [
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
        if (empty($this->weatherData) || empty($this->hijriDate)) {
            $this->getData();
        }

        $temp = $this->weatherData['temperature_2m'] ?? '--';
        $hum = $this->weatherData['relative_humidity_2m'] ?? '--';
        
        // Deskripsi Cuaca sederhana
        $code = $this->weatherData['weather_code'] ?? -1;
        $weatherDesc = 'Cerah';
        $color = 'primary';
        if (in_array($code, [51,53,55,61,63,65,80,81])) { $weatherDesc = 'Hujan'; $color = 'info'; }
        if (in_array($code, [95,96,99])) { $weatherDesc = 'Badai Petir'; $color = 'danger'; }
        if (in_array($code, [2,3,45,48])) { $weatherDesc = 'Berawan'; $color = 'gray'; }

        $cityName = $this->activeLocation['name'] ?? 'Sleman';

        return [
            Stat::make('Cuaca Hari Ini', "{$temp}°C")
                ->description("{$weatherDesc} (Klp {$hum}%) - {$cityName}")
                ->color($color),
            Stat::make('Kalender Islam', $this->hijriDate)
                ->description(Carbon::now('Asia/Jakarta')->translatedFormat('l, d F Y'))
                ->color('success'),
            Stat::make('Wilayah Pantauan', "Lokasi: {$cityName}")
                ->description("Sumber: OpenMeteo / Aladhan")
                ->color('warning'),
        ];
    }
}
