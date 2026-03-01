<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use App\Models\HealthMetric;

class WeatherForecastWidget extends Widget implements HasForms, HasTable, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    protected string $view = 'filament.widgets.weather-forecast-widget';

    protected int | string | array $columnSpan = 'full';

    public ?array $weather = null;
    public ?int $highTemp = 0;
    public ?int $lowTemp = 0;

    public function mount()
    {
        $this->loadWeatherData();
    }

    #[On('location-changed')]
    public function updateLocation()
    {
        $this->loadWeatherData();
    }

    private function getWeatherIcon($code, $isNight = false) {
        if ($code == 0) return $isNight ? 'heroicon-s-moon' : 'heroicon-s-sun';
        if (in_array($code, [1, 2])) return $isNight ? 'heroicon-s-cloud' : 'heroicon-s-cloud';
        if ($code == 3) return 'heroicon-s-cloud';
        if (in_array($code, [45, 48])) return 'heroicon-s-cloud';
        if (in_array($code, [51, 53, 55, 61, 63, 65, 80, 81, 82])) return 'heroicon-s-cloud-arrow-down';
        if (in_array($code, [95, 96, 99])) return 'heroicon-s-bolt';
        return 'heroicon-s-sun';
    }

    private function getWeatherName($code) {
        if ($code == 0) return 'Cerah';
        if (in_array($code, [1, 2])) return 'Cerah Berawan';
        if ($code == 3) return 'Mendung';
        if (in_array($code, [45, 48])) return 'Berkabut';
        if (in_array($code, [51, 53, 55])) return 'Gerimis';
        if (in_array($code, [61, 63, 65])) return 'Hujan';
        if (in_array($code, [80, 81, 82])) return 'Hujan Deras';
        if (in_array($code, [95, 96, 99])) return 'Badai Petir';
        return 'Cerah';
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

                // Insert into HealthMetrics
                $currentDate = Carbon::now('Asia/Jakarta');
                $currentHourStr = $currentDate->format('Y-m-d\TH:00');
                $hourlyTimes = $this->weather['hourly']['time'] ?? [];
                $currentIndex = array_search($currentHourStr, $hourlyTimes);
                if ($currentIndex === false) $currentIndex = 0;

                HealthMetric::where('user_id', auth()->id())->where('type', 'weather_forecast')->delete();

                $temps = [];
                for ($i = $currentIndex; $i < min($currentIndex + 24, count($hourlyTimes)); $i++) {
                    $time = Carbon::parse($hourlyTimes[$i]);
                    $hCode = $this->weather['hourly']['weather_code'][$i] ?? 0;
                    $isNight = $time->hour < 6 || $time->hour > 17;
                    $precipProb = $this->weather['hourly']['precipitation_probability'][$i] ?? 0;
                    $tempVal = round($this->weather['hourly']['temperature_2m'][$i] ?? 0);
                    $temps[] = $tempVal;
                    
                    $hourData = [
                        'time_label' => $i === $currentIndex ? 'Sekarang' : strtoupper($time->format('g A')),
                        'hour' => $time->hour,
                        'code' => $hCode,
                        'condition' => $this->getWeatherName($hCode),
                        'temp' => $tempVal,
                        'icon' => $this->getWeatherIcon($hCode, $isNight),
                        'precip_prob' => $precipProb,
                        'uv'   => $this->weather['hourly']['uv_index'][$i] ?? 0,
                        'is_night' => $isNight
                    ];

                    HealthMetric::create([
                        'user_id' => auth()->id(),
                        'date' => Carbon::now('Asia/Jakarta')->toDateString(),
                        'type' => 'weather_forecast',
                        'value' => $i - $currentIndex,
                        'details' => $hourData
                    ]);
                }

                $this->highTemp = !empty($temps) ? max($temps) : 0;
                $this->lowTemp = !empty($temps) ? min($temps) : 0;

            } else {
                $this->weather = null;
            }
        } catch (\Exception $e) {
            $this->weather = null;
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                HealthMetric::query()
                    ->where('user_id', auth()->id())
                    ->where('type', 'weather_forecast')
                    ->orderBy('value', 'asc')
            )
            ->columns([
                TextColumn::make('details.time_label')
                    ->label('Waktu')
                    ->badge()
                    ->color(fn ($state) => $state === 'Sekarang' ? 'success' : 'gray'),
                    
                TextColumn::make('details.condition')
                    ->label('Kondisi')
                    ->icon(fn ($record) => $record->details['icon'] ?? 'heroicon-o-cloud')
                    ->color(fn ($record) => $record->details['precip_prob'] > 0 ? 'info' : 'gray'),

                TextColumn::make('details.precip_prob')
                    ->label('Peluang Hujan')
                    ->formatStateUsing(fn ($state) => $state > 0 ? "{$state}%" : '-')
                    ->color('info')
                    ->badge(),

                TextColumn::make('details.temp')
                    ->label('Suhu (°C)')
                    ->badge()
                    ->color('warning')
                    ->formatStateUsing(fn ($state) => "{$state}°")
                    ->alignEnd(),
            ])
            ->paginated(false)
            ->emptyStateHeading('Data Cuaca Belum Tersedia')
            ->emptyStateDescription('Mencoba memuat ulang data...');
    }
}
