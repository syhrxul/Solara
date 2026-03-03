<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WeatherController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/weather?lat=&lng=&location_name=
     */
    public function current(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        try {
            $response = Http::timeout(5)->get('https://api.open-meteo.com/v1/forecast', [
                'latitude'       => $request->lat,
                'longitude'      => $request->lng,
                'current'        => 'temperature_2m,weather_code,relative_humidity_2m',
                'hourly'         => 'temperature_2m,relative_humidity_2m,weather_code,uv_index,visibility,precipitation_probability',
                'daily'          => 'sunrise,sunset',
                'timezone'       => 'Asia/Jakarta',
                'past_hours'     => 0,
                'forecast_hours' => 24,
            ]);

            if (!$response->successful()) {
                return $this->error('Gagal mengambil data cuaca', 502);
            }

            $data = $response->json();
            $currentDate = Carbon::now('Asia/Jakarta');
            $currentHourStr = $currentDate->format('Y-m-d\TH:00');
            $hourlyTimes = $data['hourly']['time'] ?? [];
            $currentIndex = array_search($currentHourStr, $hourlyTimes);
            if ($currentIndex === false) $currentIndex = 0;

            // Build hourly forecast
            $forecast = [];
            $temps = [];
            for ($i = $currentIndex; $i < min($currentIndex + 24, count($hourlyTimes)); $i++) {
                $time = Carbon::parse($hourlyTimes[$i]);
                $hCode = $data['hourly']['weather_code'][$i] ?? 0;
                $isNight = $time->hour < 6 || $time->hour > 17;
                $tempVal = round($data['hourly']['temperature_2m'][$i] ?? 0);
                $temps[] = $tempVal;

                $forecast[] = [
                    'time'        => $hourlyTimes[$i],
                    'time_label'  => $i === $currentIndex ? 'Sekarang' : $time->format('H:i'),
                    'temp'        => $tempVal,
                    'condition'   => $this->getWeatherName($hCode),
                    'code'        => $hCode,
                    'humidity'    => $data['hourly']['relative_humidity_2m'][$i] ?? null,
                    'uv_index'    => $data['hourly']['uv_index'][$i] ?? null,
                    'visibility'  => $data['hourly']['visibility'][$i] ?? null,
                    'precip_prob' => $data['hourly']['precipitation_probability'][$i] ?? 0,
                    'is_night'    => $isNight,
                ];
            }

            return $this->success([
                'location'  => $request->location_name ?? 'Unknown',
                'current'   => [
                    'temp'      => $data['current']['temperature_2m'] ?? null,
                    'humidity'  => $data['current']['relative_humidity_2m'] ?? null,
                    'condition' => $this->getWeatherName($data['current']['weather_code'] ?? 0),
                    'code'      => $data['current']['weather_code'] ?? 0,
                ],
                'high_temp' => !empty($temps) ? max($temps) : null,
                'low_temp'  => !empty($temps) ? min($temps) : null,
                'sunrise'   => $data['daily']['sunrise'][0] ?? null,
                'sunset'    => $data['daily']['sunset'][0] ?? null,
                'hourly'    => $forecast,
            ]);
        } catch (\Exception $e) {
            return $this->error('Gagal mengambil data cuaca: ' . $e->getMessage(), 502);
        }
    }

    private function getWeatherName($code): string
    {
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
}
