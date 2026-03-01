<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;
use App\Models\HealthMetric;
use Carbon\Carbon;
use UnitEnum;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Google\Client;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class BioHealthAnalytics extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';
    protected static UnitEnum|string|null $navigationGroup = 'Produktivitas';
    protected static ?int $navigationSort = 5;
    protected static ?string $title = 'Bio-Health Analytics';

    protected string $view = 'filament.pages.bio-health-analytics';

    public ?array $weather = null;
    public ?array $skinWarning = null;
    public ?array $sleepCorrelation = null;
    
    public string $activeTab = 'analytics';

    public function mount()
    {
        $this->activeTab = request()->query('tab', 'analytics');
        $this->loadData();
        $this->syncHealthData(30);
    }

    public function getSubheading(): Htmlable|string|null
    {
        if ($this->activeTab === 'analytics') {
            return null;
        }

        $user = auth()->user();
        $tokenExp = $user->google_token_expires_at;

        if (!$user->google_access_token) {
            return new HtmlString("<span class='text-red-500'>❌ Google Fit belum tertaut. Silakan klik 'Perbarui Izin Akses'.</span>");
        }

        if (!$tokenExp) {
            return new HtmlString("<span class='text-green-500'>✅ Terhubung ke Google Fit, namun masa aktif token tidak diketahui.</span>");
        }

        $expiresAt = Carbon::parse($tokenExp);
        if ($expiresAt->isPast()) {
            return new HtmlString("<span class='text-warning-500'>⚠️ Token akses sesi terakhirmy habis " . $expiresAt->diffForHumans() . ". Sistem otomatis pakai Refresh Token.</span>");
        }

        return new HtmlString("<span class='text-green-500'>✅ Google Fit aktif. Token akses sesi habis " . $expiresAt->diffForHumans() . ".</span>");
    }

    protected function getHeaderActions(): array
    {
        $actions = [
            Action::make('tab_analytics')
                ->label('📊 Analytics')
                ->color($this->activeTab === 'analytics' ? 'primary' : 'gray')
                ->extraAttributes(['class' => '!rounded-full'])
                ->url(request()->fullUrlWithQuery(['tab' => 'analytics'])),
                
            Action::make('tab_log_tidur')
                ->label('🌙 Log Tidur')
                ->color($this->activeTab === 'log_tidur' ? 'primary' : 'gray')
                ->extraAttributes(['class' => '!rounded-full'])
                ->url(request()->fullUrlWithQuery(['tab' => 'log_tidur'])),
        ];
        
        if ($this->activeTab === 'log_tidur') {
            $actions[] = Action::make('sync')
                ->label('Tarik Data Terbaru')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->action('syncHealthData');
                
            $actions[] = Action::make('reconnect')
                ->label('Perbarui Izin Akses')
                ->icon('heroicon-o-key')
                ->url(url('/auth/google/redirect'))
                ->color('warning');
        }

        return $actions;
    }

    protected function getHeaderWidgets(): array
    {
        if ($this->activeTab === 'analytics') {
            return [
                \App\Filament\Pages\Widgets\BioHealthOverview::make([
                    'weather' => $this->weather,
                    'skinWarning' => $this->skinWarning,
                    'sleepCorrelation' => $this->sleepCorrelation,
                ]),
            ];
        }

        return [
            \App\Filament\Pages\Widgets\SleepOverview::class,
            \App\Filament\Pages\Widgets\SleepChart::class,
        ];
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
                'current' => 'temperature_2m,weather_code,relative_humidity_2m',
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
                    'lat' => $activeLocation['lat'],
                    'lng' => $activeLocation['lng'],
                    'humidity' => $data['current']['relative_humidity_2m'] ?? '--',
                    'weather_code' => $data['current']['weather_code'] ?? -1,
                ];

                $statusColor = 'success';
                $statusIcon = 'heroicon-o-check-circle';
                $title = "Normal & Terjaga";
                $message = "Kondisi lingkungan hari ini ideal (Suhu {$temp}°C, UV: {$uvIndex}). Sirkulasi tubuh dan kelembapan kulit dalam kondisi aman sepenuhnya.";
                $tips = [
                    "Aman beraktivitas di luar", 
                    "Jaga hidrasi standar (2L/hari)", 
                    "Gunakan pelembap wajah ringan"
                ];

                if ($temp > 33 || $uvIndex > 7) {
                    $statusColor = 'danger';
                    $statusIcon = 'heroicon-o-exclamation-triangle';
                    $title = "Fase Ekstrem (Waspada)";
                    $message = "Sakit & dehidrasi mengancam. Index UV ({$uvIndex}) & Suhu ({$temp}°C) melampaui batas wajar. Hindari matahari langsung agar tidak merusak skin-barrier.";
                    $tips = [
                        "Wajib Sunscreen SPF 50+ & Reapply", 
                        "Stop aktivitas outdoor di jam 11-14", 
                        "Gunakan kacamata UV & Masker", 
                        "Standby minuman elektrolit dingin"
                    ];
                } elseif ($temp > 30 || $uvIndex > 5) {
                    $statusColor = 'warning';
                    $statusIcon = 'heroicon-o-fire';
                    $title = "Cukup Terik";
                    $message = "Potensi kusam dan dehidrasi ringan karena paparan panas medium ke tinggi ({$temp}°C, UV: {$uvIndex}).";
                    $tips = [
                        "Minimal Sunscreen SPF 30+", 
                        "Bawa botol minum cadangan",
                        "Hindari pakaian warna gelap (menyerap panas)"
                    ];
                }

                $this->skinWarning = [
                    'color' => $statusColor,
                    'icon' => $statusIcon,
                    'title' => $title,
                    'message' => $message,
                    'tips' => $tips,
                ];
            } else {
                throw new \Exception('API Error');
            }
        } catch (\Exception $e) {
            $this->weather = null;
            $this->skinWarning = [
                'color' => 'gray',
                'icon' => 'heroicon-o-x-circle',
                'title' => 'Gagal Mengambil Data',
                'message' => 'Data cuaca gagal ditarik dari server Open-Meteo.',
                'tips' => ['Cek koneksi internet'],
            ];
        }

        // Correlation with Sleep Data
        $metrics = HealthMetric::where('user_id', auth()->id())
            ->where('type', 'sleep')
            ->orderBy('date', 'desc')
            ->take(5)
            ->get();

        $sleepDuration = 0;
        
        if ($metrics->isNotEmpty()) {
            $recentDate = $metrics->first()->date->toDateString();
            $recentSleep = $metrics->filter(function($m) use ($recentDate) {
                return $m->date->toDateString() === $recentDate;
            });
            $sleepDuration = $recentSleep->sum('value');
        }
        
        if ($sleepDuration > 0 && $this->weather) {
            $statusColor = 'info';
            $statusIcon = 'heroicon-o-check-badge';
            $title = "Siap Tempur";
            $message = "Durasi " . round($sleepDuration, 1) . " jam sudah cukup meregenerasi sel otak. Kapasitas analisa kognitif dan ketahanan fisik Anda hari ini di angka yang solid.";
            $tips = [
                "Jadwalkan pekerjaan tersulit (Deep Work) di pagi hari", 
                "Olahraga ringan untuk memompa detak jantung",
                "Pertahankan konsistensi jam tidur malam ini"
            ];

            if ($sleepDuration < 6) {
                $statusColor = 'warning';
                $statusIcon = 'heroicon-o-battery-50';
                $title = "Performa Turun Ekstrem";
                $message = "Defisit pemulihan akibat tidur hanya " . round($sleepDuration, 1) . " jam. Anda berisiko kehilangan fokus instan, brain-fog (otak buntu), dan mudah merasa kelelahan (fatigue).";
                
                if (isset($this->weather['temperature']) && $this->weather['temperature'] > 30) {
                    $message .= " Suhu panas hari ini mempercepat tingkat dehidrasi dan stress tubuh akibat kelelahan.";
                    $tips = [
                        "Dilarang olahraga kardio siang ini", 
                        "Power Nap (Tidur Siang) WAJIB max 20 menit", 
                        "Stop Kafein setelah jam 2 siang",
                        "Minum elektrolit ekstra untuk saraf otak"
                    ];
                } else {
                    $message .= " Antisipasi 'sugar-crash' atau rasa ngantuk akut pada pertengahan hari.";
                    $tips = [
                        "Doping kopi / teh hangat sebelum mulai kerja", 
                        "Push tugas paling penting sekarang juga", 
                        "Selang jeda istirahat mata setiap 45 menit"
                    ];
                }
            } elseif ($sleepDuration >= 7) {
                 $statusColor = 'success';
                 $statusIcon = 'heroicon-o-battery-100';
                 $title = "Puncak Stamina (100%)";
                 $message = "Kualitas Deep Sleep luar biasa (" . round($sleepDuration, 1) . " jam). Imun tubuh sedang ter-buff, regenerasi sel maksimal, dan otak memproses memori secara sempurna.";
                 if (isset($this->weather['temperature']) && $this->weather['temperature'] <= 30) {
                     $message .= " Cuaca mendukung daya tahan konsentrasi lama.";
                     $tips = [
                         "Cocok untuk olahraga intensif (Gym/Lari)", 
                         "Lakukan problem-solving arsitektur sulit", 
                         "Otomatisasi rutinitas harianmu"
                     ];
                 } else {
                     $tips = [
                         "Fokus pada pekerjaan konseptual berat", 
                         "Bantah cuaca terik dengan tetap di AC", 
                         "Amankan to-do list terpenting hari ini"
                     ];
                 }
            }

            $this->sleepCorrelation = [
                'color' => $statusColor,
                'icon' => $statusIcon,
                'title' => $title,
                'message' => $message,
                'tips' => $tips,
            ];
        } else {
            $this->sleepCorrelation = [
                'color' => 'gray',
                'icon' => 'heroicon-o-information-circle',
                'title' => 'Data Kurang',
                'message' => 'Tidak dapat menemukan data tidur yang memadai.',
                'tips' => ['Tarik data terbaru dari Google Fit di menu Log Waktu Tidur'],
            ];
        }
    }

    private function getClient()
    {
        $user = auth()->user();
        if (!$user->google_access_token) return null;

        $client = new Client();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        
        $client->setAccessToken([
            'access_token' => $user->google_access_token,
            'refresh_token' => $user->google_refresh_token,
            'expires_in' => $user->google_token_expires_at ? Carbon::parse($user->google_token_expires_at)->diffInSeconds(Carbon::now(), false) * -1 : 0,
        ]);

        if ($client->isAccessTokenExpired()) {
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                $token = $client->getAccessToken();
                if(!isset($token['error'])){
                    $user->update([
                        'google_access_token' => $token['access_token'],
                        'google_refresh_token' => $token['refresh_token'] ?? $user->google_refresh_token,
                        'google_token_expires_at' => Carbon::now()->addSeconds($token['expires_in'] ?? 3599),
                    ]);
                } else {
                    return null;
                }
            } else {
                return null;
            }
        }

        return $client;
    }

    public function syncHealthData($daysBack = 30)
    {
        $client = $this->getClient();
        if (!$client) return;

        $token = $client->getAccessToken()['access_token'];
        $user = auth()->user();

        try {
            HealthMetric::where('user_id', $user->id)->where('type', 'sleep')->delete();

            $sessionsResponse = Http::withToken($token)
                ->get('https://www.googleapis.com/fitness/v1/users/me/sessions', [
                    'activityType' => 72,
                    'startTime' => Carbon::today()->subDays($daysBack)->toRfc3339String(),
                    'endTime' => Carbon::now()->toRfc3339String()
                ]);

            if ($sessionsResponse->successful()) {
                $sessions = $sessionsResponse->json('session');
                if (!empty($sessions)) {
                    $sleepIntervals = [];

                    foreach ($sessions as $session) {
                        $startMs = (int) $session['startTimeMillis'];
                        $endMs = (int) $session['endTimeMillis'];
                        
                        $durationH = ($endMs - $startMs) / 1000 / 3600;
                        if ($durationH > 18 || $durationH < 0.16) {
                            continue;
                        }

                        $sleepIntervals[] = [
                            'start' => $startMs,
                            'end' => $endMs
                        ];
                    }

                    usort($sleepIntervals, fn($a, $b) => $a['start'] <=> $b['start']);

                    $mergedIntervals = [];
                    foreach ($sleepIntervals as $interval) {
                        if (empty($mergedIntervals)) {
                            $mergedIntervals[] = $interval;
                        } else {
                            $last = &$mergedIntervals[count($mergedIntervals) - 1];
                            if ($interval['start'] <= ($last['end'] + 1800000)) {
                                $last['end'] = max($last['end'], $interval['end']);
                            } else {
                                $mergedIntervals[] = $interval;
                            }
                        }
                    }

                    foreach ($mergedIntervals as $merged) {
                        $startC = Carbon::createFromTimestampMs($merged['start'])->timezone('Asia/Jakarta');
                        $endC = Carbon::createFromTimestampMs($merged['end'])->timezone('Asia/Jakarta');
                        
                        $dateLabel = $endC->toDateString();
                        $durationHours = ($merged['end'] - $merged['start']) / 1000 / 3600;
                        
                        $sleepDetails = [
                            'rem' => 0, 'light' => $durationHours, 'deep' => 0, 'awake' => 0,
                            'time_bed' => $startC->format('H:i'),
                            'time_wakeup' => $endC->format('H:i'),
                            'score' => $durationHours >= 7 ? 'Sangat Baik' : ($durationHours >= 5 ? 'Cukup' : 'Kurang'),
                            'start_timestamp' => $merged['start'],
                            'end_timestamp' => $merged['end']
                        ];

                        HealthMetric::create([
                            'user_id' => $user->id, 
                            'date' => $dateLabel, 
                            'type' => 'sleep',
                            'value' => $durationHours, 
                            'details' => $sleepDetails
                        ]);
                    }
                }
            }

            // SPO2
            $startTimeMillis = Carbon::today()->subDays($daysBack)->getTimestampMs();
            $endTimeMillis = Carbon::now()->getTimestampMs();

            $spo2Response = Http::withToken($token)
                ->post('https://www.googleapis.com/fitness/v1/users/me/dataset:aggregate', [
                    'aggregateBy' => [['dataTypeName' => 'com.google.oxygen_saturation']],
                    'bucketByTime' => ['durationMillis' => 86400000],
                    'startTimeMillis' => $startTimeMillis,
                    'endTimeMillis' => $endTimeMillis
                ]);

            if ($spo2Response->successful()) {
                $buckets = $spo2Response->json('bucket');
                if (!empty($buckets)) {
                    foreach ($buckets as $bucket) {
                        if (isset($bucket['dataset'][0]['point']) && count($bucket['dataset'][0]['point']) > 0) {
                            $spo2Val = round($bucket['dataset'][0]['point'][0]['value'][0]['fpVal'], 1);
                            $bucketDate = Carbon::createFromTimestampMs($bucket['startTimeMillis'])->toDateString();
                            HealthMetric::updateOrCreate(
                                ['user_id' => $user->id, 'date' => $bucketDate, 'type' => 'spo2'],
                                ['value' => $spo2Val, 'details' => []]
                            );
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Google Fit Sync Error: ' . $e->getMessage());
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(HealthMetric::query()->where('user_id', auth()->id())->where('type', 'sleep')->orderBy('date', 'desc'))
            ->columns([
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable()
                    ->searchable(),
                
                TextColumn::make('value')
                    ->label('Total Waktu Tidur')
                    ->formatStateUsing(fn ($state) => floor($state) . 'j ' . round(($state - floor($state)) * 60) . 'm')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('details.score')
                    ->label('Kualitas')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'Sangat Baik' => 'success',
                        'Cukup' => 'warning',
                        'Kurang' => 'danger',
                        default => 'gray',
                    }),
                    
                TextColumn::make('details.time_bed')
                    ->label('Mulai Tidur')
                    ->icon('heroicon-o-moon'),
                    
                TextColumn::make('details.time_wakeup')
                    ->label('Bangun')
                    ->icon('heroicon-o-sun'),
            ])
            ->emptyStateHeading('Belum ada Histori Tidur')
            ->emptyStateDescription('Tarik data dari Google Fit terlebih dahulu. Pastikan Smartwatch Anda sudah Sinkron dengan Google Fit di Telepon.');
    }
}
