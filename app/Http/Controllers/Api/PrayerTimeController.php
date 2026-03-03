<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PrayerTimeController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/prayer-times?lat=&lng=&date=
     */
    public function index(Request $request)
    {
        $request->validate([
            'lat'  => 'required|numeric',
            'lng'  => 'required|numeric',
            'date' => 'nullable|date',
        ]);

        try {
            $date = $request->date
                ? Carbon::parse($request->date)
                : Carbon::now('Asia/Jakarta');

            $dateStr = $date->format('d-m-Y');

            $response = Http::timeout(5)->get("https://api.aladhan.com/v1/timings/$dateStr", [
                'latitude'  => $request->lat,
                'longitude' => $request->lng,
                'method'    => 20,
            ]);

            if (!$response->successful()) {
                return $this->error('Gagal mengambil jadwal sholat', 502);
            }

            $timings = $response->json('data.timings');
            $hijri = $response->json('data.date.hijri');

            $sessions = [
                ['sesi' => 'Imsak',    'jam' => $timings['Imsak'] ?? '-',   'keterangan' => 'Berhenti makan & minum'],
                ['sesi' => 'Subuh',    'jam' => $timings['Fajr'] ?? '-',    'keterangan' => 'Sholat Subuh'],
                ['sesi' => 'Terbit',   'jam' => $timings['Sunrise'] ?? '-', 'keterangan' => 'Matahari terbit'],
                ['sesi' => 'Dzuhur',   'jam' => $timings['Dhuhr'] ?? '-',   'keterangan' => 'Sholat Dzuhur'],
                ['sesi' => 'Ashar',    'jam' => $timings['Asr'] ?? '-',     'keterangan' => 'Sholat Ashar'],
                ['sesi' => 'Terbenam', 'jam' => $timings['Sunset'] ?? '-',  'keterangan' => 'Matahari terbenam'],
                ['sesi' => 'Maghrib',  'jam' => $timings['Maghrib'] ?? '-', 'keterangan' => 'Sholat Maghrib / Buka Puasa'],
                ['sesi' => 'Isya',     'jam' => $timings['Isha'] ?? '-',    'keterangan' => 'Sholat Isya'],
            ];

            return $this->success([
                'date'       => $date->toDateString(),
                'hijri_date' => $hijri ? "{$hijri['day']} {$hijri['month']['en']} {$hijri['year']} H" : null,
                'location'   => $request->location_name ?? 'Unknown',
                'sessions'   => $sessions,
            ]);
        } catch (\Exception $e) {
            return $this->error('Gagal mengambil jadwal sholat: ' . $e->getMessage(), 502);
        }
    }
}
