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
    protected static UnitEnum|string|null $navigationGroup = 'Produktivitas';
    protected static ?int $navigationSort = 5;
    protected static ?string $title = 'Bio-Health Analytics';

    protected string $view = 'filament.pages.bio-health-analytics';

    public ?array $weather = null;
    public ?array $skinWarning = null;
    public ?array $sleepCorrelation = null;

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

                $statusColor = 'emerald';
                $statusIcon = 'heroicon-o-check-circle';
                $title = "Aman, Kulit Terjaga";
                $message = "Cuaca hari ini di {$activeLocation['name']} terbilang bersahabat. Suhu {$temp}°C dengan index UV {$uvIndex}.";
                $tips = ["Kamu bisa beraktivitas diluar ruangan lebih leluasa", "Gunakan pelembap ringan", "Minum air putih secukupnya"];

                if ($temp > 33 || $uvIndex > 7) {
                    $statusColor = 'red';
                    $statusIcon = 'heroicon-o-exclamation-triangle';
                    $title = "Peringatan Ekstrem!";
                    $message = "Suhu luar ruangan sangat merusak ({$temp}°C) dengan index radiasi UV bahaya ({$uvIndex}). Mending di dalam ruangan aja!";
                    $tips = ["Wajib gunakan Sunscreen SPF 50+", "Jangan lupa re-apply setiap 2 jam", "Gunakan Hoodie & Masker jika bermotor", "Wajib minum ekstra air putih"];
                } elseif ($temp > 30 || $uvIndex > 5) {
                    $statusColor = 'orange';
                    $statusIcon = 'heroicon-o-fire';
                    $title = "Waspada Cuaca Terik";
                    $message = "Hari ini di {$activeLocation['name']} lumayan panas ({$temp}°C, UV: {$uvIndex}). Pastikan hidrasimu aman ya Rul.";
                    $tips = ["Gunakan Sunscreen minimal SPF 30+", "Bawa botol minum dari rumah", "Gunakan kacamata hitam jika matahari silau"];
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
                'color' => 'slate',
                'icon' => 'heroicon-o-x-circle',
                'title' => 'Gagal Mengambil Data',
                'message' => 'Data cuaca gagal ditarik dari server Open-Meteo.',
                'tips' => ['Cek koneksi internet'],
            ];
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
            $statusColor = 'blue';
            $statusIcon = 'heroicon-o-check-badge';
            $title = "Energi Sangat Baik";
            $message = "Durasi tidur semalam: " . round($sleepDuration, 1) . " jam. Waktu yang cukup untuk optimalisasi produktivitas.";
            $tips = ["Manfaatkan energi penuh ini untuk aktivitas berat", "Tetap disiplin pada rutinitas tidurmu"];

            if ($sleepDuration < 6) {
                $statusColor = 'orange';
                $statusIcon = 'heroicon-o-battery-50';
                $title = "Warning: Kurang Tidur";
                $message = "Tidur semalam hanya " . round($sleepDuration, 1) . " jam.";
                
                if (isset($this->weather['temperature']) && $this->weather['temperature'] > 30) {
                    $message .= " Mengingat cuaca panas hari ini, risiko dehidrasi dan fatigue (kelelahan mental) sangat tinggi.";
                    $tips = ["Jangan memaksakan kerja fisik berat", "Wajib rebahan / Power Nap 20 menit di siang hari", "Perbanyak minum isotonik (pocari dll)", "Limit kafein maksimal 1 cup"];
                } else {
                    $message .= " Kurang fokus mungkin menyerang di pertengahan jam kerja.";
                    $tips = ["Minum kopi/teh sebelum mulai bekerja", "Kerjakan tugas paling berat di jam-jam pertama", "Sisihkan waktu istirahat sejenak"];
                }
            } elseif ($sleepDuration >= 7) {
                 $statusColor = 'emerald';
                 $statusIcon = 'heroicon-o-battery-100';
                 $title = "Performa Optimal!";
                 $message = "Luar biasa! Tidurmu sangat cukup (" . round($sleepDuration, 1) . " jam). Recovery otak & fisik sedang pada puncaknya.";
                 if (isset($this->weather['temperature']) && $this->weather['temperature'] <= 30) {
                     $message .= " Cuacanya pun sedang bagus.";
                     $tips = ["Lakukan olahraga ringan selagi rileks", "Fase ini sempurna untuk merancang ide & tugas rumit", "Pertahankan kedisiplinan jadwal tidur."];
                 } else {
                     $tips = ["Tubuh sedang prima untuk Deep Work", "Jaga asupan cairan di tengah cuaca panas", "Fokus selesaikan goals hari ini"];
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
                'color' => 'slate',
                'icon' => 'heroicon-o-information-circle',
                'title' => 'Data Kurang',
                'message' => 'Tidak dapat menemukan data tidur yang memadai.',
                'tips' => ['Tarik data terbaru dari Google Fit di menu Log Waktu Tidur'],
            ];
        }
    }
}
