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
                $title = "Aman, Kulit Terjaga";
                $message = "Cuaca hari ini di {$activeLocation['name']} terbilang bersahabat. Suhu {$temp}°C dengan index UV {$uvIndex}. Kulit Anda tidak terlalu terekspos bahaya sinar matahari berlebih yang dapat menyebabkan penuaan dini dan kerusakan sel epidermal.";
                $tips = [
                    "Kamu bisa beraktivitas di luar ruangan dengan leluasa.", 
                    "Gunakan pelembap (moisturizer) ringan agar skin-barrier tidak kering walau cuaca sejuk.", 
                    "Tetap pertahankan konsumsi air putih minimal 2 liter sehari untuk memastikan hidrasi dari dalam tubuh tetap aman."
                ];

                if ($temp > 33 || $uvIndex > 7) {
                    $statusColor = 'danger';
                    $statusIcon = 'heroicon-o-exclamation-triangle';
                    $title = "Peringatan Ekstrem Cuaca!";
                    $message = "Perhatian! Suhu luar ruangan saat ini sangat membakar ({$temp}°C) dengan index radiasi UV tingkat bahaya ({$uvIndex}). Berada di luar tanpa perlindungan bisa membakar kulit (sunburn) dalam waktu kurang dari 15 menit dan merusak kolagen alami.";
                    $tips = [
                        "Wajib gunakan Sunscreen spektrum luas (minimal SPF 50+ & PA++++).", 
                        "Re-apply sunscreen secara disiplin setiap 2-3 jam sekali, apalagi jika berkeringat.", 
                        "Sangat disarankan memakai pakaian berlengan panjang (Hoodie/Jaket UV) & Masker jika harus berkendara.", 
                        "Tingkatkan konsumsi air putih, dan segera tambah cairan elektrolit / isotonik jika badan mengeluarkan keringat berlebih."
                    ];
                } elseif ($temp > 30 || $uvIndex > 5) {
                    $statusColor = 'warning';
                    $statusIcon = 'heroicon-o-fire';
                    $title = "Waspada Paparan Terik";
                    $message = "Hari ini di {$activeLocation['name']} cuacanya lumayan panas ({$temp}°C, UV: {$uvIndex}). Paparan UV tingkat menengah secara terus-menerus bisa memicu hiperpigmentasi (flek hitam) dan membuat kulit jadi sangat kusam (produksi sebum meningkat).";
                    $tips = [
                        "Gagas proteksi dengan rutin mengoleskan Sunscreen (minimal SPF 30+) sebelum keluar ruangan.", 
                        "Rutin basuh muka / gunakan facial wash berbahan lembut jika wajah mulai terasa lengket dan sangat berminyak.",
                        "Bawa botol minum dari rumah & jaga asupan air secara berkala untuk melembapkan kulit dari dalam.", 
                        "Gunakan kacamata hitam pelindung UV agar area sekitar mata tidak keriput karena refleks menyipitkan mata saat silau."
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
            $title = "Kapasitas Energi Baik";
            $message = "Durasi tidur semalam terekam " . round($sleepDuration, 1) . " jam. Durasi ini cukup memadai untuk merangsang pemulihan fungsi kognitif dan pembelahan sel-sel baru di seluruh tubuh. Otak Anda seharusnya siap untuk menyerap dan memproses informasi secara optimal.";
            $tips = [
                "Jadwalkan High-Focus Work (pekerjaan paling butuh konsentrasi tinggi) di jam-jam produktif pagi Anda.", 
                "Pertahankan konsistensi jam tidur ini agar jam biologis sirkadian harian Anda menjadi stabil.",
                "Seimbangkan energi ini dengan sarapan atau makan siang bernutrisi protein (telur/daging) agar tidak drop saat sore."
            ];

            if ($sleepDuration < 6) {
                $statusColor = 'warning';
                $statusIcon = 'heroicon-o-battery-50';
                $title = "Zona Bahaya: Kurang Tidur";
                $message = "Bahaya, sistem biologis Anda kurang recharge. Tidur semalam hanya " . round($sleepDuration, 1) . " jam. Kurang dari batas minimal akan sangat keras memengaruhi korteks prefrontal (bagian otak analitik), menurunkan kemampuan membuat keputusan (decision making), merusak memori jangka pendek, serta mengaktifkan hormon stres kortisol yang akan berefek pada mood-swing dan lapar seharian.";
                
                if (isset($this->weather['temperature']) && $this->weather['temperature'] > 30) {
                    $message .= " Diperparah dengan terik dan suhu panas hari ini, detak jantung dan thermoregulasi tubuh memaksa metabolisme bekerja ganda. Risiko mengalami kelelahan mental akut (brain fatigue) dan dehidrasi jauh di atas batas normal.";
                    $tips = [
                        "DILARANG KERAS melakukan aktivitas fisik kardio / olahraga berat melampaui limit hari ini, agar tidak collaps!", 
                        "Wajib jalankan 'Power Nap' (tidur kilat) selama maksimal 20-30 menit di sela-sela jam siang untuk melancarkan sirkulasi oksigen mikro otak.", 
                        "Setel batasan mutlak: Tidak ada Asupan Kafein / Kopi di atas jam 3 sore. Bayar utang tidur (sleep debt) nanti malam dengan tidur lebih awal secara damai.",
                        "Gempur asupan hidrasi dengan air isotonik/mineral dingin di siang hari. Otak yang lelah butuh elektrolit untuk hantaran impuls listrik saraf."
                    ];
                } else {
                    $message .= " Mengingat hal ini, wajar jika Anda merasa lambat (sugar-crash) dan rentan dihantam rasa kantuk luar biasa pada pertengahan sesi kerja Anda.";
                    $tips = [
                        "Piculah saraf Anda dengan secangkir Kopi Hijau / Teh Hitam hangat tepat 30 menit sebelum sesi produktif dimulai.", 
                        "Paksa diri Anda membabat habis tugas krusial pada urutan teratas (Eat the Frog), jangan ditunda ke sore hari.", 
                        "Terapkan Pomodoro Modifikasi: Paksa break peregangan badan selama 5 menit untuk setiap 45 menit fokus di depan layar (Micro-breaks).",
                        "Malam ini jangan banyak scrolling. Matikan lampu demi perbaikan sekresi Melatonin."
                    ];
                }
            } elseif ($sleepDuration >= 7) {
                 $statusColor = 'success';
                 $statusIcon = 'heroicon-o-battery-100';
                 $title = "Performa Sangat Optimal!";
                 $message = "Luar biasa hebat! Tidur Anda sangat ideal dan berkuantitas tinggi (" . round($sleepDuration, 1) . " jam penuh). Fase sakral Deep Sleep dan REM dipastikan tuntas membilas racun-racun sisa metabolisme (amyloid beta) di saraf. Daya tahan imun, ledakan otot, serta regenerasi sel saraf Anda saat ini sedang berada di siklus puncak 100%.";
                 if (isset($this->weather['temperature']) && $this->weather['temperature'] <= 30) {
                     $message .= " Alam juga sedang berpihak; suhu lingkungan nan sejuk akan memperpanjang endurance otak menjaga mood tetap stabil sepanjang hari penuh.";
                     $tips = [
                         "Sambut hari dengan olahraga pemacu endorfin seperti lari pagi atau kalistenik intens. Badan Andalah mesin bertenaga tinggi saat ini.", 
                         "Jadikan fase keemasan saraf ini untuk merancang ide gila banting stir, brainstorming solusi arsitektur sulit, atau marathon coding logika.", 
                         "Kunci keberhasilan adalah repetisi. Pastikan malam ini handphone masuk laci agar durasi jam tidur ini berhasil diamankan untuk esok hari!"
                     ];
                 } else {
                     $tips = [
                         "Inilah the real 'Deep Work'. Tutup semua distraksi sosial dan ledakkan produktivitas Anda pada layar monitor layaknya super-komputer.", 
                         "Karena sistem kardiovaskular stabil, lawan hawa luar ruangan yang panas dengan tetap berteduh dan minum secara elegan.", 
                         "Libas habis seluruh To-Do-List di Jira/Tasks Board hari ini tanpa ampun!"
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
                        
                        $dateLabel = $startC->toDateString();
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
