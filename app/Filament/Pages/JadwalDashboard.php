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

    public $activeLocation = 'Sleman';

    public function mount()
    {
        $this->loadPrayerTimes();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('switch_sleman')
                ->label('Sleman')
                ->icon('heroicon-o-map-pin')
                ->color(fn () => $this->activeLocation === 'Sleman' ? 'primary' : 'gray')
                ->action(function () {
                    $this->activeLocation = 'Sleman';
                    $this->loadPrayerTimes();
                    $this->dispatch('location-changed', location: 'Sleman')->to(\App\Filament\Pages\Widgets\JadwalOverview::class);
                }),
            Action::make('switch_purbalingga')
                ->label('Purbalingga')
                ->icon('heroicon-o-map-pin')
                ->color(fn () => $this->activeLocation === 'Purbalingga' ? 'primary' : 'gray')
                ->action(function () {
                    $this->activeLocation = 'Purbalingga';
                    $this->loadPrayerTimes();
                    $this->dispatch('location-changed', location: 'Purbalingga')->to(\App\Filament\Pages\Widgets\JadwalOverview::class);
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Pages\Widgets\JadwalOverview::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 3;
    }

    public function loadPrayerTimes()
    {
        $locations = [
            'Sleman' => ['lat' => -7.71556, 'lng' => 110.35556],
            'Purbalingga' => ['lat' => -7.3885, 'lng' => 109.3639],
        ];

        $lat = $locations[$this->activeLocation]['lat'];
        $lng = $locations[$this->activeLocation]['lng'];

        try {
            $dateStr = Carbon::now('Asia/Jakarta')->format('d-m-Y');
            $dateSq = Carbon::now('Asia/Jakarta')->toDateString();
            $response = Http::get("https://api.aladhan.com/v1/timings/$dateStr", [
                'latitude' => $lat,
                'longitude' => $lng,
                'method' => 20,
            ]);

            if ($response->successful()) {
                $timings = $response->json('data.timings');
                
                $sessions = [
                    ['id' => 1, 'sesi' => 'Imsak', 'jam' => $timings['Imsak'] ?? '-', 'keterangan' => 'Berhenti makan & minum (Puasa)'],
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
                        'value' => $ses['id'], // Gunakan value untuk mengurutkan (sortir) tabel 1 sampai 8
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
            ->emptyStateHeading('Satelit Sedang Offline')
            ->emptyStateDescription('Tidak Dapat Memuat Jadwal Ibadah.');
    }
}
