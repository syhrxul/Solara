<x-filament-widgets::widget>
    @if($this->weather)
        @php
            $weather = $this->weather;
            
            $getWeatherName = function($code) {
                if ($code == 0) return 'Cerah';
                if (in_array($code, [1, 2])) return 'Cerah Berawan';
                if ($code == 3) return 'Mendung';
                if (in_array($code, [45, 48])) return 'Berkabut';
                if (in_array($code, [51, 53, 55])) return 'Gerimis';
                if (in_array($code, [61, 63, 65])) return 'Hujan';
                if (in_array($code, [80, 81, 82])) return 'Hujan Deras';
                if (in_array($code, [95, 96, 99])) return 'Badai Petir';
                return 'Cerah';
            };

            $currentDate = \Carbon\Carbon::now('Asia/Jakarta');
            $currentHourStr = $currentDate->format('Y-m-d\TH:00');
            $hourlyTimes = $weather['hourly']['time'] ?? [];
            $currentIndex = array_search($currentHourStr, $hourlyTimes);
            if ($currentIndex === false) $currentIndex = 0;

            $tempNow = $weather['current']['temperature_2m'] ?? '--';
            $codeNow = $weather['current']['weather_code'] ?? 0;
            $humidityNow = $weather['current']['relative_humidity_2m'] ?? '--';
            
            // Max daily details for current day
            $uvMax = max(array_slice($weather['hourly']['uv_index'] ?? [0], $currentIndex, 24));
            $visibilityData = array_slice($weather['hourly']['visibility'] ?? [0], $currentIndex, 24);
            $avgVisibility = count($visibilityData) ? (array_sum($visibilityData) / count($visibilityData)) / 1000 : 0; // in km

            $sunriseStr = $weather['daily']['sunrise'][0] ?? null;
            $sunsetStr = $weather['daily']['sunset'][0] ?? null;
            $sunrise = $sunriseStr ? \Carbon\Carbon::parse($sunriseStr)->format('H:i') : '--:--';
            $sunset = $sunsetStr ? \Carbon\Carbon::parse($sunsetStr)->format('H:i') : '--:--';
            $isGlobalNight = $currentDate->hour < 6 || $currentDate->hour >= 18;
            $currentDesc = $getWeatherName($codeNow);
            $locationName = $weather['location_name'] ?? 'Lokasi';
        @endphp

        <x-filament::section class="overflow-hidden">
            <!-- Jumbotron Cuaca -->
            <div class="relative rounded-xl p-5 text-white overflow-hidden shadow-sm bg-gradient-to-br {{ $isGlobalNight ? 'from-slate-800 to-slate-900' : 'from-blue-500 to-sky-400' }}">
                <div class="relative z-10 flex flex-col items-center justify-center text-center">
                    <h2 class="text-xl font-medium tracking-wide">{{ $locationName }}</h2>
                    <p class="text-5xl font-semibold mt-2 mb-1 drop-shadow-md">{{ round($tempNow) }}°</p>
                    <p class="text-sm font-medium tracking-wide drop-shadow">{{ $currentDesc }}</p>
                    <div class="flex items-center space-x-3 mt-1 opacity-90 text-xs">
                        <span>H: {{ $this->highTemp }}°</span>
                        <span>L: {{ $this->lowTemp }}°</span>
                    </div>
                </div>
            </div>

            <!-- Tabel Data 24 Jam Dari DB Filament -->
            <div class="mt-4 w-full [&_.fi-ta-empty-state]:bg-transparent">
                {{ $this->table }}
            </div>



            <style>
                .hide-scrollbar::-webkit-scrollbar {
                    display: none;
                }
                .hide-scrollbar {
                    -ms-overflow-style: none;
                    scrollbar-width: none;
                }
            </style>
        </x-filament::section>
    @else
        <x-filament::section>
            <div class="flex flex-col items-center justify-center p-6 text-gray-500">
                @svg('heroicon-o-cloud-slash', 'w-12 h-12 mb-4 opacity-50')
                <p>Data cuaca gagal dimuat. Periksa koneksi internet Anda.</p>
            </div>
        </x-filament::section>
    @endif
</x-filament-widgets::widget>
