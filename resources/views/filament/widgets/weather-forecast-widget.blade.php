<x-filament-widgets::widget>
    @if($this->weather)
        @php
            $weather = $this->weather;
            
            $getWeatherIcon = function($code, $isNight = false) {
                if ($code == 0) return $isNight ? 'heroicon-s-moon' : 'heroicon-s-sun';
                if (in_array($code, [1, 2])) return $isNight ? 'heroicon-s-cloud' : 'heroicon-s-cloud';
                if ($code == 3) return 'heroicon-s-cloud';
                if (in_array($code, [45, 48])) return 'heroicon-s-cloud';
                if (in_array($code, [51, 53, 55, 61, 63, 65, 80, 81, 82])) return 'heroicon-s-cloud-arrow-down';
                if (in_array($code, [95, 96, 99])) return 'heroicon-s-bolt';
                return 'heroicon-s-sun';
            };

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
            
            // Extract next 24 hours
            $next24Hours = [];
            for ($i = $currentIndex; $i < min($currentIndex + 24, count($hourlyTimes)); $i++) {
                $time = \Carbon\Carbon::parse($hourlyTimes[$i]);
                $hCode = $weather['hourly']['weather_code'][$i] ?? 0;
                $isNight = $time->hour < 6 || $time->hour > 17;
                $precipProb = $weather['hourly']['precipitation_probability'][$i] ?? 0;
                $next24Hours[] = [
                    'time_label' => $i === $currentIndex ? 'Sekarang' : strtoupper($time->format('g A')),
                    'hour' => $time->hour,
                    'code' => $hCode,
                    'temp' => round($weather['hourly']['temperature_2m'][$i] ?? 0),
                    'icon' => $getWeatherIcon($hCode, $isNight),
                    'precip_prob' => $precipProb,
                    'uv'   => $weather['hourly']['uv_index'][$i] ?? 0,
                    'is_night' => $isNight
                ];
            }

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
                        <span>H: {{ max(array_column($next24Hours, 'temp')) }}°</span>
                        <span>L: {{ min(array_column($next24Hours, 'temp')) }}°</span>
                    </div>
                </div>

                <!-- 24 Hours Forecast Table -->
                <div class="relative z-10 mt-6 pt-4 border-t border-white/20">
                    <p class="text-sm font-semibold mb-3 opacity-90">Prakiraan 24 Jam Kedepan</p>
                    <div class="max-h-60 overflow-y-auto pr-2 hide-scrollbar">
                        <table class="w-full text-left whitespace-nowrap">
                            <tbody class="divide-y divide-white/10">
                                @foreach($next24Hours as $idx => $hourData)
                                    <tr class="hover:bg-white/5 transition-colors group">
                                        <td class="py-2.5 w-16 font-semibold opacity-90 text-sm">
                                            {{ $hourData['time_label'] }}
                                        </td>
                                        <td class="py-2.5">
                                            <div class="flex items-center space-x-3">
                                                @svg($hourData['icon'], 'w-5 h-5 ' . ($hourData['icon'] == 'heroicon-s-sun' ? 'text-yellow-300' : ($hourData['icon'] == 'heroicon-s-moon' ? 'text-slate-200' : 'text-white')))
                                                <span class="text-sm font-medium opacity-90">{{ $getWeatherName($hourData['code']) }}</span>
                                            </div>
                                        </td>
                                        <td class="py-2.5 text-right font-medium text-sm w-20 {{ $hourData['precip_prob'] > 0 ? 'text-sky-300' : 'text-white/20' }}">
                                            @if($hourData['precip_prob'] > 0)
                                                {{ $hourData['precip_prob'] }}%
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="py-2.5 text-right font-bold text-lg w-12">
                                            {{ $hourData['temp'] }}°
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Detail Grid -->
            <div class="flex flex-nowrap overflow-x-auto md:grid md:grid-cols-4 gap-3 mt-3 hide-scrollbar pb-2">
                <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-3 flex flex-col items-start justify-between shadow-sm border border-gray-100 dark:border-gray-700 shrink-0 w-36 md:w-auto">
                    <div class="flex items-center space-x-1.5 text-primary-600 dark:text-primary-400 mb-1">
                        @svg('heroicon-o-sun', 'w-4 h-4')
                        <span class="text-[10px] font-bold uppercase tracking-wider">Indeks UV</span>
                    </div>
                    <div>
                        <p class="text-lg font-semibold dark:text-white">{{ $uvMax }}</p>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">{{ $uvMax > 5 ? 'Tinggi' : 'Rendah' }}</p>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-3 flex flex-col items-start justify-between shadow-sm border border-gray-100 dark:border-gray-700 shrink-0 w-36 md:w-auto">
                    <div class="flex items-center space-x-1.5 text-success-600 dark:text-success-400 mb-1">
                        @svg('heroicon-o-clock', 'w-4 h-4')
                        <span class="text-[10px] font-bold uppercase tracking-wider">Matahari</span>
                    </div>
                    <div class="w-full">
                        <div class="flex justify-between items-end border-b border-gray-200 dark:border-gray-700 pb-0.5 mb-0.5">
                            <span class="text-[10px] text-gray-500">Terbit</span>
                            <span class="text-xs font-semibold dark:text-white">{{ $sunrise }}</span>
                        </div>
                        <div class="flex justify-between items-end mt-0.5">
                            <span class="text-[10px] text-gray-500">Terbenam</span>
                            <span class="text-xs font-semibold dark:text-white">{{ $sunset }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-3 flex flex-col items-start justify-between shadow-sm border border-gray-100 dark:border-gray-700 shrink-0 w-36 md:w-auto">
                    <div class="flex items-center space-x-1.5 text-info-600 dark:text-info-400 mb-1">
                        @svg('heroicon-o-cloud', 'w-4 h-4')
                        <span class="text-[10px] font-bold uppercase tracking-wider">Kelembapan</span>
                    </div>
                    <div>
                        <p class="text-lg font-semibold dark:text-white">{{ $humidityNow }}%</p>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">Titik embun rata-rata</p>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-3 flex flex-col items-start justify-between shadow-sm border border-gray-100 dark:border-gray-700 shrink-0 w-36 md:w-auto">
                    <div class="flex items-center space-x-1.5 text-warning-600 dark:text-warning-400 mb-1">
                        @svg('heroicon-o-eye', 'w-4 h-4')
                        <span class="text-[10px] font-bold uppercase tracking-wider">Jarak Pandang</span>
                    </div>
                    <div>
                        <p class="text-lg font-semibold dark:text-white">{{ round($avgVisibility, 1) }} <span class="text-[10px]">km</span></p>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">Visibilitas sangat jernih</p>
                    </div>
                </div>
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
