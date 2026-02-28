<x-filament-panels::page>
    @if($activeTab === 'analytics')
        <div class="mt-4 grid grid-cols-1 xl:grid-cols-2 gap-6">
            
            {{-- Skin / Weather Detailed Analysis --}}
            @if(isset($skinWarning))
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm flex flex-col h-full">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2.5 bg-{{ $skinWarning['color'] ?? 'primary' }}-100 dark:bg-{{ $skinWarning['color'] ?? 'primary' }}-500/20 rounded-lg">
                        <x-filament::icon icon="heroicon-o-face-smile" class="w-6 h-6 text-{{ $skinWarning['color'] ?? 'primary' }}-600 dark:text-{{ $skinWarning['color'] ?? 'primary' }}-400" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">Analisis Kulit & Cuaca</h3>
                        <p class="text-sm font-medium text-{{ $skinWarning['color'] ?? 'primary' }}-600 dark:text-{{ $skinWarning['color'] ?? 'primary' }}-400">{{ $skinWarning['title'] ?? '-' }}</p>
                    </div>
                </div>

                <div class="prose dark:prose-invert max-w-none text-sm text-gray-600 dark:text-gray-400 leading-relaxed font-medium mb-6">
                    {{ $skinWarning['message'] ?? 'Memuat analisis kulit...' }}
                </div>

                @if(isset($skinWarning['tips']) && is_array($skinWarning['tips']) && count($skinWarning['tips']) > 0)
                    <div class="mt-auto pt-5 border-t border-gray-100 dark:border-gray-800">
                        <h4 class="font-bold text-sm mb-3 text-gray-900 dark:text-gray-100 flex items-center gap-2">
                            <x-filament::icon icon="heroicon-s-light-bulb" class="w-4 h-4 text-warning-500" />
                            Rekomendasi Tindakan
                        </h4>
                        <ul class="space-y-3">
                            @foreach($skinWarning['tips'] as $tip)
                                <li class="flex items-start gap-3">
                                    <div class="mt-0.5 rounded-full p-1 bg-{{ $skinWarning['color'] ?? 'primary' }}-100 dark:bg-{{ $skinWarning['color'] ?? 'primary' }}-500/20 text-{{ $skinWarning['color'] ?? 'primary' }}-600 dark:text-{{ $skinWarning['color'] ?? 'primary' }}-400 shrink-0">
                                        <x-filament::icon icon="heroicon-m-check" class="w-3 h-3 font-bold" />
                                    </div>
                                    <span class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{{ $tip }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
            @endif

            {{-- Sleep Detailed Analysis --}}
            @if(isset($sleepCorrelation))
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm flex flex-col h-full">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2.5 bg-{{ $sleepCorrelation['color'] ?? 'primary' }}-100 dark:bg-{{ $sleepCorrelation['color'] ?? 'primary' }}-500/20 rounded-lg">
                        <x-filament::icon icon="heroicon-o-moon" class="w-6 h-6 text-{{ $sleepCorrelation['color'] ?? 'primary' }}-600 dark:text-{{ $sleepCorrelation['color'] ?? 'primary' }}-400" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">Analisis Tidur & Kinerja</h3>
                        <p class="text-sm font-medium text-{{ $sleepCorrelation['color'] ?? 'primary' }}-600 dark:text-{{ $sleepCorrelation['color'] ?? 'primary' }}-400">{{ $sleepCorrelation['title'] ?? '-' }}</p>
                    </div>
                </div>

                <div class="prose dark:prose-invert max-w-none text-sm text-gray-600 dark:text-gray-400 leading-relaxed font-medium mb-6">
                    {{ $sleepCorrelation['message'] ?? 'Memuat analisis tidur...' }}
                </div>

                @if(isset($sleepCorrelation['tips']) && is_array($sleepCorrelation['tips']) && count($sleepCorrelation['tips']) > 0)
                    <div class="mt-auto pt-5 border-t border-gray-100 dark:border-gray-800">
                        <h4 class="font-bold text-sm mb-3 text-gray-900 dark:text-gray-100 flex items-center gap-2">
                            <x-filament::icon icon="heroicon-s-bolt" class="w-4 h-4 text-warning-500" />
                            Strategi Hari Ini
                        </h4>
                        <ul class="space-y-3">
                            @foreach($sleepCorrelation['tips'] as $tip)
                                <li class="flex items-start gap-3">
                                    <div class="mt-0.5 rounded-full p-1 bg-{{ $sleepCorrelation['color'] ?? 'primary' }}-100 dark:bg-{{ $sleepCorrelation['color'] ?? 'primary' }}-500/20 text-{{ $sleepCorrelation['color'] ?? 'primary' }}-600 dark:text-{{ $sleepCorrelation['color'] ?? 'primary' }}-400 shrink-0">
                                        <x-filament::icon icon="heroicon-m-chevron-double-right" class="w-3 h-3 font-bold" />
                                    </div>
                                    <span class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{{ $tip }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
            @endif

        </div>
    @elseif($activeTab === 'log_tidur')
        <div class="mt-4">
            {{ $this->table }}
        </div>
    @endif
</x-filament-panels::page>
