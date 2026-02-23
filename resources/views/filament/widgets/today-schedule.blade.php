<div class="p-4 rounded-xl border border-violet-200 dark:border-violet-800 bg-gradient-to-br from-violet-50 to-indigo-50 dark:from-violet-950/40 dark:to-indigo-950/40">
    <div class="flex items-center gap-2 mb-4">
        <div class="p-2 rounded-lg bg-violet-500 text-white">
            <x-heroicon-o-academic-cap class="w-5 h-5" />
        </div>
        <div>
            <h3 class="font-bold text-gray-900 dark:text-white text-base">📅 Jadwal Kuliah Hari Ini</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $this->getTodayName() }}</p>
        </div>
    </div>

    @php $schedules = $this->scheduleList; @endphp

    @if($schedules->isEmpty())
        <div class="text-center py-6 text-gray-400 dark:text-gray-500">
            <x-heroicon-o-check-circle class="w-10 h-10 mx-auto mb-2 text-green-400" />
            <p class="text-sm">Tidak ada kuliah hari ini 🎉</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($schedules as $schedule)
                @php
                    $now = \Carbon\Carbon::now();
                    $start = \Carbon\Carbon::parse($schedule->waktu_mulai);
                    $end = $schedule->waktu_selesai ? \Carbon\Carbon::parse($schedule->waktu_selesai) : null;
                    $isOngoing = $now->gte($start) && ($end ? $now->lte($end) : true);
                    $isPast = $end ? $now->gt($end) : $now->gt($start->addHours(2));
                @endphp
                <div class="flex items-start gap-3 p-3 rounded-lg {{ $isOngoing ? 'bg-violet-100 dark:bg-violet-900/40 border border-violet-300 dark:border-violet-700' : ($isPast ? 'bg-gray-50 dark:bg-gray-800/50 opacity-60' : 'bg-white dark:bg-gray-800/80 border border-gray-100 dark:border-gray-700') }} transition-all">
                    <div class="text-center min-w-[60px]">
                        <p class="text-xs font-bold {{ $isOngoing ? 'text-violet-600 dark:text-violet-400' : 'text-gray-500 dark:text-gray-400' }}">
                            {{ substr($schedule->waktu_mulai, 0, 5) }}
                        </p>
                        @if($schedule->waktu_selesai)
                            <p class="text-xs text-gray-400">{{ substr($schedule->waktu_selesai, 0, 5) }}</p>
                        @endif
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="font-semibold text-sm text-gray-900 dark:text-white truncate">
                                {{ $schedule->mata_kuliah }}
                            </p>
                            @if($isOngoing)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-violet-500 text-white animate-pulse">
                                    🔴 LIVE
                                </span>
                            @endif
                        </div>
                        <div class="flex flex-wrap gap-2 mt-1">
                            @if($schedule->kelas)
                                <span class="text-xs text-gray-500 dark:text-gray-400">📚 Kelas {{ $schedule->kelas }}</span>
                            @endif
                            @if($schedule->dosen)
                                <span class="text-xs text-gray-500 dark:text-gray-400">👨‍🏫 {{ $schedule->dosen }}</span>
                            @endif
                            @if($schedule->media_pembelajaran)
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    💻 {{ $schedule->media_pembelajaran }}
                                </span>
                            @endif
                            @if($schedule->ruangan)
                                <span class="text-xs text-gray-500 dark:text-gray-400">📍 {{ $schedule->ruangan }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="text-right shrink-0">
                        <span class="text-xs font-medium px-2 py-1 rounded-full
                            {{ $isOngoing ? 'bg-violet-200 dark:bg-violet-800 text-violet-700 dark:text-violet-300' :
                               ($isPast ? 'bg-gray-200 dark:bg-gray-700 text-gray-500' : 'bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300') }}">
                            {{ $schedule->sks }} SKS
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
