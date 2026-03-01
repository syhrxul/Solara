<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\On;

class WeatherDetailStatsWidget extends BaseWidget
{
    public ?array $weatherData = null;

    public function mount()
    {
        $this->loadData();
    }

    #[On('location-changed')]
    public function updateLocation()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $settings = auth()->user()->settings ?? [];
        $activeLocation = $settings['jadwal_location'] ?? [
            'name' => 'Sleman',
            'lat'  => -7.71556,
            'lng'  => 110.35556,
        ];

        try {
            $response = Http::timeout(5)->get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => $activeLocation['lat'],
                'longitude' => $activeLocation['lng'],
                'current' => 'relative_humidity_2m',
                'hourly' => 'uv_index,visibility',
                'daily' => 'sunrise,sunset',
                'timezone' => 'Asia/Jakarta',
                'past_hours' => 0,
                'forecast_hours' => 24,
            ]);

            if ($response->successful()) {
                $this->weatherData = $response->json();
            } else {
                $this->weatherData = null;
            }
        } catch (\Exception $e) {
            $this->weatherData = null;
        }
    }

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        if (!$this->weatherData) {
            return [
                Stat::make('Memuat data...', '-'),
            ];
        }

        $weather = $this->weatherData;

        $currentDate = Carbon::now('Asia/Jakarta');
        $currentHourStr = $currentDate->format('Y-m-d\TH:00');
        $hourlyTimes = $weather['hourly']['time'] ?? [];
        $currentIndex = array_search($currentHourStr, $hourlyTimes);
        if ($currentIndex === false) $currentIndex = 0;

        $humidityNow = $weather['current']['relative_humidity_2m'] ?? '--';
        $uvMax = max(array_slice($weather['hourly']['uv_index'] ?? [0], $currentIndex, 24));
        $visibilityData = array_slice($weather['hourly']['visibility'] ?? [0], $currentIndex, 24);
        $avgVisibility = count($visibilityData) ? (array_sum($visibilityData) / count($visibilityData)) / 1000 : 0; // in km

        $sunriseStr = $weather['daily']['sunrise'][0] ?? null;
        $sunsetStr = $weather['daily']['sunset'][0] ?? null;
        $sunrise = $sunriseStr ? Carbon::parse($sunriseStr)->format('H:i') : '--:--';
        $sunset = $sunsetStr ? Carbon::parse($sunsetStr)->format('H:i') : '--:--';

        return [
            Stat::make('Indeks UV', $uvMax)
                ->description($uvMax > 5 ? 'Tinggi hari ini.' : 'Rendah hari ini.')
                ->color('primary'),

            Stat::make('Matahari', "{$sunrise} - {$sunset}")
                ->description('Terbit & Terbenam')
                ->color('success'),

            Stat::make('Kelembapan', "{$humidityNow}%")
                ->description('Titik embun rata-rata.')
                ->color('info'),

            Stat::make('Jarak Pandang', round($avgVisibility, 1) . ' km')
                ->description('Visibilitas sangat jernih.')
                ->color('warning'),
        ];
    }
}
