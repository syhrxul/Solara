<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Livewire\Attributes\On;

class WeatherForecastWidget extends Widget
{
    protected string $view = 'filament.widgets.weather-forecast-widget';

    protected int | string | array $columnSpan = 'full';

    public ?array $weather = null;

    public function mount()
    {
        $this->loadWeatherData();
    }

    #[On('location-changed')]
    public function updateLocation()
    {
        $this->loadWeatherData();
    }

    public function loadWeatherData()
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
                'current' => 'temperature_2m,weather_code,relative_humidity_2m',
                'hourly' => 'temperature_2m,relative_humidity_2m,weather_code,uv_index,visibility,precipitation_probability',
                'daily' => 'sunrise,sunset',
                'timezone' => 'Asia/Jakarta',
                'past_hours' => 0,
                'forecast_hours' => 24,
            ]);

            if ($response->successful()) {
                $this->weather = $response->json();
                $this->weather['location_name'] = $activeLocation['name'];
            } else {
                $this->weather = null;
            }
        } catch (\Exception $e) {
            $this->weather = null;
        }
    }
}
