<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;
use App\Models\HealthMetric;
use Carbon\Carbon;
use UnitEnum;
use BackedEnum;

class BioHealthAnalytics extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';
    protected static string|UnitEnum|null $navigationGroup = 'Produktivitas';
    protected static ?int $navigationSort = 5;
    protected static ?string $title = 'Bio-Health Analytics';

    protected static string $view = 'filament.pages.bio-health-analytics';

    public ?array $weather = null;
    public ?string $skinWarning = null;
    public ?string $sleepCorrelation = null;

    public function mount()
    {
        $this->loadData();
    }

    protected function loadData()
    {
        // 1. Fetch Weather (Location from Jadwal Dashboard)
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
                'current' => 'temperature_2m,weather_code',
                'hourly' => 'uv_index',
                'timezone' => 'Asia/Jakarta',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $temp = $data['current']['temperature_2m'] ?? 0;
                
                // Get current hour's UV index
                $currentHour = Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:00');
                $uvIndex = 0;
                if (isset($data['hourly']['time']) && isset($data['hourly']['uv_index'])) {
                    $index = array_search($currentHour, $data['hourly']['time']);
                    if ($index !== false) {
                        $uvIndex = $data['hourly']['uv_index'][$index] ?? 0;
                    }
                }

                $this->weather = [
                    'temperature' => $temp,
                    'uv_index' => $uvIndex,
                    'location' => $activeLocation['name'],
                ];

                if ($temp > 32 || $uvIndex > 6) {
                    $this->skinWarning = "Rul, hari ini panas banget di {$activeLocation['name']} (Suhu: {$temp}°C, UV: {$uvIndex}), jangan lupa pake sunscreen/skincare pagi.";
                } else {
                    $this->skinWarning = "Cuaca hari ini di {$activeLocation['name']} cukup aman (Suhu: {$temp}°C, UV: {$uvIndex}). Tetap jaga hidrasi kulit.";
                }
            } else {
                throw new \Exception('API Error');
            }
        } catch (\Exception $e) {
            $this->weather = null;
            $this->skinWarning = "Gagal mengambil data cuaca.";
        }

        // 2. Correlation with Sleep Data
        $metrics = HealthMetric::where('user_id', auth()->id())
            ->where('type', 'sleep')
            ->orderBy('date', 'desc')
            ->take(5)
            ->get();

        $sleepDuration = 0;
        
        if ($metrics->isNotEmpty()) {
            // Use the most recent day's total duration
            $recentDate = $metrics->first()->date->toDateString();
            $recentSleep = $metrics->filter(function($m) use ($recentDate) {
                return $m->date->toDateString() === $recentDate;
            });
            $sleepDuration = $recentSleep->sum('value');
        }
        
        if ($sleepDuration > 0 && $this->weather) {
            if ($sleepDuration < 6 && $this->weather['temperature'] > 30) {
                $this->sleepCorrelation = "Tidurmu kurang semalam (" . round($sleepDuration, 1) . " jam) dan hari ini panas. Jangan lupa banyak minum air agar tidak dehidrasi dan cepat lelah.";
            } elseif ($sleepDuration >= 7 && $this->weather['temperature'] <= 30) {
                 $this->sleepCorrelation = "Tidurmu cukup (" . round($sleepDuration, 1) . " jam) dan cuaca mendukung. Semangat menjalani hari!";
            } else {
                $this->sleepCorrelation = "Durasi tidur semalam: " . round($sleepDuration, 1) . " jam. Tetap jaga produktivitas!";
            }
        } else {
            $this->sleepCorrelation = "Sinkronkan data tidur dari Google Fit di menu Log Waktu Tidur untuk melihat korelasi.";
        }
    }
}
