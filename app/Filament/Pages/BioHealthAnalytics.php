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

        // 2. Correlation with Sleep Data (7 Days Analysis)
        $metrics = HealthMetric::where('user_id', auth()->id())
            ->where('type', 'sleep')
            ->orderBy('date', 'desc')
            ->take(7)
            ->get();

        $wokeUpHourFormatted = '06:00';

        if ($metrics->isNotEmpty()) {
            

            $totalSleepDays = $metrics->count();
            $avgDuration = $metrics->avg('value');
            
            $sleepTimes = [];
            $wakeTimes = [];
            
            $goldenHourHits = 0;
            
            foreach ($metrics as $m) {

                $timeBedStr = $m->details['time_bed'] ?? '23:00';
                $tH = (int) explode(':', $timeBedStr)[0];
                $tM = (int) explode(':', $timeBedStr)[1];
                $bedMinutes = ($tH * 60) + $tM;
                if ($tH < 12) {
                    $bedMinutes += (24 * 60);
                }
                $sleepTimes[] = $bedMinutes;

                if ($tH >= 22 || $tH < 2) {
                    $goldenHourHits++;
                }

                $wakeStr = $m->details['time_wakeup'] ?? '06:00';
                $wH = (int) explode(':', $wakeStr)[0];
                $wM = (int) explode(':', $wakeStr)[1];
                $wakeTimes[] = ($wH * 60) + $wM;
            }

            $durationScore = 40;
            if ($avgDuration < 7) {
                $durationScore = max(0, 40 - ((7 - $avgDuration) * 10));
            } elseif ($avgDuration > 9) {
                $durationScore = max(0, 40 - (($avgDuration - 9) * 10)); 
            }

            $consistencyScore = 30;
            if ($totalSleepDays > 1) {
                $maxWake = max($wakeTimes);
                $minWake = min($wakeTimes);
                $diffHours = ($maxWake - $minWake) / 60;

                if ($diffHours > 2) {
                    $consistencyScore = max(0, 30 - (($diffHours - 2) * 5)); 
                }
            }


            $goldenRatio = $goldenHourHits / $totalSleepDays;
            $goldenHourScore = round($goldenRatio * 30);


            $sleepScore = round($durationScore + $consistencyScore + $goldenHourScore);

            $recentSleep = $metrics->first();
            $recentTimeBed = $recentSleep->details['time_bed'] ?? '23:00';
            $recentTimeWakeup = $recentSleep->details['time_wakeup'] ?? '06:00';
            $wokeUpHourFormatted = $recentTimeWakeup;
            $hBed = (int) explode(':', $recentTimeBed)[0];


            $statusColor = 'success';
            $statusIcon = 'heroicon-o-check-badge';
            $title = "Analisis Tidur Mingguan (Score: {$sleepScore}/100)";

            $message = "Berdasarkan rutinitas {$totalSleepDays} hari terakhir, sistem menyimpulkan kualitas tidur Anda berada di tingkat <strong>";
            
            $tips = [];

            if ($sleepScore >= 85) {
                $statusColor = 'success';
                $statusIcon = 'heroicon-o-star';
                $message .= "Sangat Baik (Excellent)</strong>.";
                $message .= " Rata-rata tidur " . round($avgDuration, 1) . " jam/hari dan ritme bangun Anda sangat konsisten. Golden hour tercapai dengan mantap.";
                
                $tips[] = "Pertahankan ritme ini karena sangat bagus untuk fokus harianmu.";
                $tips[] = "Lanjutkan produktivitas tinggi Anda di pagi hari saat energi lagi full.";
            } elseif ($sleepScore >= 65) {
                $statusColor = 'info';
                $statusIcon = 'heroicon-o-hand-thumb-up';
                $message .= "Cukup Baik (Fair)</strong>.";
                $message .= " Rata-rata tidur " . round($avgDuration, 1) . " jam/hari. Pola tidur sudah lumayan, tapi ada beberapa malam di atas jam 2 pagi atau jadwal bangun beda jauh.";

                $tips[] = "Supaya makin bugar besok harinya, coba usahakan tidur sebelum jam 23:00.";
                $tips[] = "Usahakan saat akhir pekan tidak bangun terlalu siang agar ritme jam tubuh tidak kaget.";
            } elseif ($sleepScore >= 40) {
                $statusColor = 'warning';
                $statusIcon = 'heroicon-o-exclamation-triangle';
                $message .= "Kurang (Poor)</strong>.";
                $message .= " Rata-rata " . round($avgDuration, 1) . " jam/hari. Polamu sedang sedikit berantakan belakangan ini, mulai sering begadang di luar area Golden Hour.";

                $tips[] = "Minimalisir main gadget 1 jam sebelum target tidurmu ya.";
                $tips[] = "Boleh banget cobain Power Nap / tidur siang kilat (10-20 menit) buat nge-boost sisa harimu.";
            } else {
                $statusColor = 'danger';
                $statusIcon = 'heroicon-o-shield-exclamation';
                $message .= "Sangat Kurang (Critical)</strong>.";
                $message .= " Rata-rata tidur cuma " . round($avgDuration, 1) . " jam/hari dan jarak jam bangunnya kurang konsisten. Badan pasti berasa lumayan capek nih buat jalanin hari.";

                $tips[] = "Coba malam ini tidur lebih awal yuk untuk bayar hutang tidurmu yang numpuk.";
                $tips[] = "Kurangi minum kopi/teh terlalu sore supaya malam harinya lebih mudah terlelap.";
            }

            // Tambahkan insight kebiasaan terakhir
            $strHabit = "<br><em class='text-xs opacity-70 mt-2 block'>Catatan Ekstra: Semalam tidur jam {$recentTimeBed}, bangun {$recentTimeWakeup}.</em>";
            $message .= $strHabit;

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
                'title' => 'Data Tidur Kosong',
                'message' => 'Belum ada histori tidur minimal 1 hari terakhir.',
                'tips' => ['Tarik Histori Tidur dari Data Google Fit di tab Log Tidur'],
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

                            // Bisa berjemur setelah bangun tidur dan batasi hanya sebelum jam 10 pagi 
                            // agar tidak gosong/hitam (fokus ke morning sun).
                            if ($tMinutes >= $wokeUpMinutes && $tH <= 10) {
                                $bestSunbathingTimes[] = $timeOnly;
                            }
                        }
                    }
                }
            }

            if (!empty($bestSunbathingTimes)) {
                $startJemur = $bestSunbathingTimes[0];
                $endJemur = end($bestSunbathingTimes);
                $messageEnv .= " Dilihat dari rutinitas bangun Anda ({$wokeUpHourFormatted}), <strong>waktu berjemur terbaik (Sintesis Vitamin D maksimal)</strong> hari ini ada di sekitar jam <strong>{$startJemur} s/d " . Carbon::parse($endJemur)->addHour()->format('H:i') . "</strong>. ";
                $tipsEnv[] = "Berjemurlah asik sekitar 10-15 menit di periode pagi tersebut tanpa sunscreen untuk dapetin Vit. D murni. Cahaya di jam ini aman banget bikin kamu tetap *glowing* tanpa bikin gosong.";
            } else {
                 $messageEnv .= " Saat ini sudah tidak ada jendela berjemur pagi yang ideal buat Anda. Hindari memaksakan jemur siang/sore karena gampangnya cuma bikin kulit item/rusak.";
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
