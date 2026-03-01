<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use UnitEnum;
use BackedEnum;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use App\Models\HealthMetric;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class JadwalDashboard extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-sun';
    protected static string|UnitEnum|null $navigationGroup = 'Produktivitas';
    protected static ?int $navigationSort = 4;
    protected static ?string $title = 'Jadwal & Cuaca Harian';
    protected string $view = 'filament.pages.jadwal-dashboard';

    public static function canAccess(): bool
    {
        $settings = auth()->user()->settings ?? [];
        return $settings['module_health'] ?? true;
    }

    public array $activeLocation = [];

    public function mount()
    {
        $settings = auth()->user()->settings ?? [];
        
        $this->activeLocation = $settings['jadwal_location'] ?? [
            'name' => 'Sleman',
            'lat'  => -7.71556,
            'lng'  => 110.35556,
        ];

        $this->loadPrayerTimes();
    }

    protected function getHeaderActions(): array
    {
        $settings = auth()->user()->settings ?? [];
        $savedLocations = $settings['saved_locations'] ?? [
            'Sleman' => ['name' => 'Sleman', 'lat' => -7.71556, 'lng' => 110.35556],
        ];

        $locationOptions = collect($savedLocations)->mapWithKeys(function ($loc) {
            return [$loc['name'] => $loc['name']];
        })->toArray();

        return [
            Action::make('select_location')
                ->label('Pilih Lokasi')
                ->icon('heroicon-o-map-pin')
                ->color('success')
                ->modalHeading('Pilih Lokasi Tersimpan')
                ->form([
                    \Filament\Forms\Components\Select::make('location_name')
                        ->label('Lokasi')
                        ->options($locationOptions)
                        ->default($this->activeLocation['name'] ?? 'Sleman')
                        ->required(),
                ])
                ->action(function (array $data) use ($savedLocations) {
                    $selected = $savedLocations[$data['location_name']];
                    $this->setActiveLocation($selected);
                }),

            Action::make('search_location')
                ->label('Tambah Lokasi Baru')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->modalHeading('Cari & Simpan Wilayah Baru')
                ->modalDescription('Masukkan nama kota/kabupaten. Lokasi ini akan disimpan untuk dipilih nanti.')
                ->modalWidth('md')
                ->form([
                    TextInput::make('city')
                        ->label('Nama Kota/Kabupaten')
                        ->placeholder('Contoh: Jakarta, Surabaya, Malang')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->searchAndSetLocation($data['city']);
                }),

            Action::make('delete_location')
                ->label('Hapus')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->modalHeading('Hapus Lokasi Tersimpan')
                ->form([
                    \Filament\Forms\Components\Select::make('location_name')
                        ->label('Pilih Lokasi yang ingin dihapus')
                        ->options($locationOptions)
                        ->required(),
                ])
                ->action(function (array $data) use ($savedLocations) {
                    if ($data['location_name'] === 'Sleman' && count($savedLocations) === 1) {
                        Notification::make()->title('Tidak dapat menghapus lokasi terakhir.')->danger()->send();
                        return;
                    }

                    unset($savedLocations[$data['location_name']]);
                    
                    $user = auth()->user();
                    $settings = $user->settings ?? [];
                    $settings['saved_locations'] = $savedLocations;
                    $user->update(['settings' => $settings]);

                    Notification::make()->title("Lokasi {$data['location_name']} dihapus.")->success()->send();

                    // If active location was deleted, switch to the first available
                    if (($this->activeLocation['name'] ?? '') === $data['location_name']) {
                        $firstLoc = reset($savedLocations);
                        $this->setActiveLocation($firstLoc);
                    }
                }),
        ];
    }

    protected function setActiveLocation(array $locationData)
    {
        $this->activeLocation = $locationData;

        // Save active location to user settings
        $user = auth()->user();
        $settings = $user->settings ?? [];
        $settings['jadwal_location'] = $locationData;
        $user->update(['settings' => $settings]);

        $this->loadPrayerTimes();
        $this->dispatch('location-changed')->to(\App\Filament\Widgets\JadwalOverview::class);
        $this->dispatch('location-changed')->to(\App\Filament\Widgets\WeatherForecastWidget::class);
        $this->dispatch('location-changed')->to(\App\Filament\Widgets\WeatherDetailStatsWidget::class);
    }

    public function searchAndSetLocation(string $city)
    {
        try {
            // Geocoding API from Open-Meteo
            $response = Http::get('https://geocoding-api.open-meteo.com/v1/search', [
                'name' => $city,
                'count' => 1,
                'language' => 'id',
            ]);

            if ($response->successful() && !empty($response->json('results'))) {
                $result = $response->json('results')[0];
                
                $locationData = [
                    'name' => $result['name'],
                    'lat'  => $result['latitude'],
                    'lng'  => $result['longitude'],
                ];

                // Save to saved_locations
                $user = auth()->user();
                $settings = $user->settings ?? [];
                $savedLocations = $settings['saved_locations'] ?? [
                    'Sleman' => ['name' => 'Sleman', 'lat' => -7.71556, 'lng' => 110.35556],
                ];
                $savedLocations[$result['name']] = $locationData;
                $settings['saved_locations'] = $savedLocations;
                $user->update(['settings' => $settings]);

                Notification::make()
                    ->title("Lokasi '{$result['name']}' berhasil ditambahkan")
                    ->success()
                    ->send();

                $this->setActiveLocation($locationData);
            } else {
                Notification::make()
                    ->title("Lokasi '$city' tidak ditemukan")
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title("Gagal mencari lokasi")
                ->danger()
                ->send();
        }
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\JadwalOverview::class,
            \App\Filament\Widgets\WeatherForecastWidget::class,
            \App\Filament\Widgets\WeatherDetailStatsWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 3;
    }

    public function loadPrayerTimes()
    {
        $lat = $this->activeLocation['lat'];
        $lng = $this->activeLocation['lng'];

        try {
            $dateStr = Carbon::now('Asia/Jakarta')->format('d-m-Y');
            $dateSq = Carbon::now('Asia/Jakarta')->toDateString();
            array_filter([]);  
            
            $response = Http::get("https://api.aladhan.com/v1/timings/$dateStr", [
                'latitude' => $lat,
                'longitude' => $lng,
                'method' => 20,
            ]);

            if ($response->successful()) {
                $timings = $response->json('data.timings');
                
                $sessions = [
                    ['id' => 1, 'sesi' => 'Imsak', 'jam' => $timings['Imsak'] ?? '-', 'keterangan' => 'Berhenti makan & minum'],
                    ['id' => 2, 'sesi' => 'Subuh', 'jam' => $timings['Fajr'] ?? '-', 'keterangan' => 'Sholat Subuh'],
                    ['id' => 3, 'sesi' => 'Terbit', 'jam' => $timings['Sunrise'] ?? '-', 'keterangan' => 'Matahari terbit'],
                    ['id' => 4, 'sesi' => 'Dzuhur', 'jam' => $timings['Dhuhr'] ?? '-', 'keterangan' => 'Sholat Dzuhur'],
                    ['id' => 5, 'sesi' => 'Ashar', 'jam' => $timings['Asr'] ?? '-', 'keterangan' => 'Sholat Ashar'],
                    ['id' => 6, 'sesi' => 'Terbenam', 'jam' => $timings['Sunset'] ?? '-', 'keterangan' => 'Matahari terbenam'],
                    ['id' => 7, 'sesi' => 'Maghrib', 'jam' => $timings['Maghrib'] ?? '-', 'keterangan' => 'Sholat Maghrib / Buka Puasa'],
                    ['id' => 8, 'sesi' => 'Isya', 'jam' => $timings['Isha'] ?? '-', 'keterangan' => 'Sholat Isya'],
                ];

                // Hapus data jadwal yang lama untuk user ini
                HealthMetric::where('user_id', auth()->id())->where('type', 'prayer_time')->delete();

                // Simpan data jadwal yang baru
                foreach ($sessions as $ses) {
                    HealthMetric::create([
                        'user_id' => auth()->id(),
                        'date' => $dateSq,
                        'type' => 'prayer_time',
                        'value' => $ses['id'], 
                        'details' => $ses
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Abaikan jika offline
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                HealthMetric::query()
                    ->where('user_id', auth()->id())
                    ->where('type', 'prayer_time')
                    ->orderBy('value', 'asc') // Urutkan berdasarkan ID dari pagi ke malam
            )
            ->columns([
                TextColumn::make('details.sesi')
                    ->label('Sesi Ibadah')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'Imsak' => 'danger',
                        'Subuh' => 'info',
                        'Terbit' => 'warning',
                        'Terbenam' => 'gray',
                        'Maghrib' => 'success',
                        default => 'gray',
                    }),
                    
                TextColumn::make('details.keterangan')
                    ->label('Keterangan Aktivitas'),

                TextColumn::make('details.jam')
                    ->label('Jam (WIB)')
                    ->icon('heroicon-o-clock')
                    ->badge()
                    ->color('primary')
                    ->alignEnd(),
            ])
            ->paginated(false)
            ->emptyStateHeading('Jadwal Belum Dimuat')
            ->emptyStateDescription('Mencoba menyinkronkan data...');
    }
}
