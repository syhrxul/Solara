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
                
                $hourlyTime = $data['hourly']['time'] ?? [];
                $hourlyUv = $data['hourly']['uv_index'] ?? [];

                if (!empty($hourlyTime) && !empty($hourlyUv)) {
                    $index = array_search($currentHour, $hourlyTime);
                    if ($index !== false) {
                        $uvIndex = $hourlyUv[$index] ?? 0;
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
                    'hourly_time' => $hourlyTime,
                    'hourly_uv' => $hourlyUv,
                ];

            } else {
                throw new \Exception('API Error');
            }
        } catch (\Exception $e) {
            $this->weather = null;
        }

        $metrics = HealthMetric::where('user_id', auth()->id())
            ->where('type', 'sleep')
            ->orderBy('date', 'desc')
            ->take(7)
            ->get();

        $sleepDuration = 0;
        $wokeUpHourFormatted = '06:00';
        $avgDuration = 0;

        if ($metrics->isNotEmpty()) {
            $recentDate = $metrics->first()->date->toDateString();
            $recentSleep = $metrics->filter(function($m) use ($recentDate) {
                return $m->date->toDateString() === $recentDate;
            });
            $sleepDuration = $recentSleep->sum('value');
            $avgDuration = $metrics->avg('value');

            // Find recent time bed and wake up
            $recentTimeBed = $recentSleep->first()->details['time_bed'] ?? '23:00';
            $recentTimeWakeup = $recentSleep->first()->details['time_wakeup'] ?? '06:00';
            
            $wokeUpHourFormatted = $recentTimeWakeup;

            // Check golden hour (23:00 to 02:00)
            $hBed = (int) explode(':', $recentTimeBed)[0];
            
            // Evaluasi jam mulai tidur (Kebiasaan)
            $strHabit = "Tidur terakhir sekitar jam $recentTimeBed, bangun jam $recentTimeWakeup. ";
            if ($hBed >= 23 || $hBed < 2) {
                $strHabit .= "Sangat baik, Anda beristirahat di rentang Golden Hour (23:00 - 02:00), optimal untuk regenerasi sel otak dan hormon tubuh.";
            } elseif ($hBed >= 2 && $hBed < 6) {
                $strHabit .= "Anda melewatkan Golden Hour (tidur di atas jam 2 pagi). Ritme sirkadian Anda mungkin tertanggu.";
            } elseif ($hBed < 23 && $hBed > 18) {
                $strHabit .= "Anda tidur cukup awal hari ini, waktu yang sangat bagus untuk pemulihan jangka panjang.";
            }

            if ($sleepDuration > 0 && $this->weather) {
                $statusColor = 'info';
                $statusIcon = 'heroicon-o-check-badge';
                $title = "Analisis Tidur & Golden Hour";
                $message = "Total tidur terakhir: " . round($sleepDuration, 1) . " jam rata-rata seminggu ini " . round($avgDuration, 1) . " jam. {$strHabit}";
                
                $tips = [];

                if ($sleepDuration < 6) {
                    $statusColor = 'warning';
                    $statusIcon = 'heroicon-o-battery-50';
                    $title = "Defisit Waktu Tidur";
                    $message .= " Kekurangan jam tidur dapat menyebabkan brain-fog dan kelelahan instan hari ini.";
                    
                    $tips[] = "Lakukan 'Power Nap' di siang hari (10-20 menit) untuk memompa kewaspadaan.";
                    $tips[] = "Redupkan layar dan hindari kafein 4-6 jam sebelum waktu tidur berikutnya agar Anda bisa tidur lebih cepat (jam 22:00).";
                    $tips[] = "Gunakan teknik relaksasi / meditasi 10 menit sebelum tidur agar bisa langsung tertidur pulas.";
                } elseif ($sleepDuration >= 7) {
                     $statusColor = 'success';
                     $statusIcon = 'heroicon-o-battery-100';
                     $title = "Kondisi Fisik Prima";
                     $message .= " Jumlah jam tidur Anda mencukupi fase Deep Sleep.";
                     $tips[] = "Hari ini ideal untuk Deep Work / pekerjaan berat yang butuh konsentrasi tinggi.";
                     $tips[] = "Coba terus pertahankan jam tidur di bawah 11 malam (Golden Hour) untuk rutinitas stabil.";
                } else {
                     $tips[] = "Durasi tidur lumayan, namun usahakan sentuh 7-8 jam.";
                     $tips[] = "Cobalah mematikan perangkat elektronik minimal 30 menit sebelum tidur untuk mempercepat ngantuk.";
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
                    'title' => 'Data Tidur Kurang',
                    'message' => 'Tarik data terbaru dari Google Fit.',
                    'tips' => ['Sinkronkan data melalui Log Tidur'],
                ];
            }
        } else {
            $this->sleepCorrelation = [
                'color' => 'gray',
                'icon' => 'heroicon-o-information-circle',
                'title' => 'Data Tidur Kosong',
                'message' => 'Belum ada histori tidur.',
                'tips' => ['Tarik dari Data Google Fit'],
            ];
        }

        // 3. Sunbathing & Env Logic (Combine Weather and Wake up time)
        if ($this->weather) {
            $temp = $this->weather['temperature'];
            $uvIndex = $this->weather['uv_index'];
            
            $statusColorEnv = 'success';
            $statusIconEnv = 'heroicon-o-sun';
            $titleEnv = "Panduan Kulit & Jemur";
            $messageEnv = "Kondisi lingkungan saat ini bersuhu {$temp}°C (UV Index: {$uvIndex}). ";
            $tipsEnv = [];

            // Rekomendasi Jam Berjemur
            $bestSunbathingTimes = [];
            $hourlyT = $this->weather['hourly_time'] ?? [];
            $hourlyU = $this->weather['hourly_uv'] ?? [];

            if (!empty($hourlyT)) {
                $todayDateStr = Carbon::now('Asia/Jakarta')->toDateString();
                foreach ($hourlyT as $idx => $t) {
                    if (str_starts_with($t, $todayDateStr)) {
                        $uvAtTime = $hourlyU[$idx] ?? 0;
                        if ($uvAtTime >= 2 && $uvAtTime <= 5) {
                            $timeOnly = Carbon::parse($t)->format('H:i');
                            
                            $wH = (int)explode(':', $wokeUpHourFormatted)[0];
                            $wM = (int)explode(':', $wokeUpHourFormatted)[1];
                            $wokeUpMinutes = ($wH * 60) + $wM;
                            
                            $tH = (int)Carbon::parse($t)->format('H');
                            $tM = (int)Carbon::parse($t)->format('i');
                            $tMinutes = ($tH * 60) + $tM;

                            // Bisa berjemur setelah bangun tidur
                            if ($tMinutes >= $wokeUpMinutes) {
                                $bestSunbathingTimes[] = $timeOnly;
                            }
                        }
                    }
                }
            }

            if (!empty($bestSunbathingTimes)) {
                $startJemur = $bestSunbathingTimes[0];
                $endJemur = end($bestSunbathingTimes);
                $messageEnv .= " Dilihat dari rutinitas bangun Anda ({$wokeUpHourFormatted}), **waktu berjemur terbaik (Sintesis Vitamin D)** hari ini antara jam **{$startJemur} s/d " . Carbon::parse($endJemur)->addHour()->format('H:i') . "**. ";
                $tipsEnv[] = "Berjemurlah asik sekitar 10-15 menit di periode tersebut tanpa sunscreen agar hormon dan mood di pagi hari membaik.";
            } else {
                 $messageEnv .= " Saat ini tidak ada jendela ideal UV aman untuk kulit atau jendela sudah terlewat.";
            }

            // Extreme weather warnings
            if ($temp > 33 || $uvIndex > 7) {
                $statusColorEnv = 'danger';
                $statusIconEnv = 'heroicon-o-exclamation-triangle';
                $titleEnv = "Fase Ekstrem (Waspada UV/Suhu)";
                $tipsEnv[] = "Wajib Sunscreen SPF 50+ & Reapply tiap 2 jam, karena UV eksterm dapat merusak skin barrier.";
                $tipsEnv[] = "Stop aktivitas outdoor langsung jam 11-14. Berteduhlah.";
                $tipsEnv[] = "Banyak minum air elektrolit karena resiko dehidrasi di suhu {$temp}°C sungguh tinggi.";
            } elseif ($temp > 30 || $uvIndex > 5) {
                $statusColorEnv = 'warning';
                $statusIconEnv = 'heroicon-o-fire';
                $titleEnv = "Cukup Terik";
                $tipsEnv[] = "Minimal Sunscreen SPF 30+ agar kulit tidak kusam.";
                $tipsEnv[] = "Perhatikan stok minuman karena tubuh lebih cepat kehausan.";
            } else {
                 $tipsEnv[] = "Suhu dan cuaca tidak terlalu terik. Aman buat beraktivitas normal.";
            }

            $this->skinWarning = [
                'color' => $statusColorEnv,
                'icon' => $statusIconEnv,
                'title' => $titleEnv,
                'message' => $messageEnv,
                'tips' => $tipsEnv,
            ];
        } else {
            $this->skinWarning = [
                'color' => 'gray',
                'icon' => 'heroicon-o-x-circle',
                'title' => 'Gagal Memuat Cuaca',
                'message' => 'API Open-Meteo sedang offline atau tidak ada sinyal internet.',
                'tips' => ['Cek konektivitas Anda.'],
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
