<x-filament-panels::page>
    @if($activeTab === 'analytics')
        @php
            $skinColor = $skinWarning['color'] ?? 'primary';
            $sleepColor = $sleepCorrelation['color'] ?? 'primary';
        @endphp
        <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
            
            {{-- Skin / Weather Detailed Analysis --}}
            @if(isset($skinWarning))
            <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 flex flex-col h-full">
                <div class="flex items-center gap-x-2 mb-2">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        Kondisi Lingkungan
                    </span>
                </div>
                
                <div class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white mb-3">
                    {{ $skinWarning['title'] ?? '-' }}
                </div>
                
                <div class="fi-color-custom flex items-start gap-2 text-sm font-medium text-custom-600 dark:text-custom-400 mb-6" style="--c-400:var(--{{ $skinColor }}-400);--c-600:var(--{{ $skinColor }}-600);">
                    <x-filament::icon icon="{{ $skinWarning['icon'] ?? 'heroicon-s-face-smile' }}" class="w-5 h-5 shrink-0 mt-0.5" />
                    <span class="leading-relaxed">
                        {{ $skinWarning['message'] ?? 'Memuat analisis kulit...' }}
                    </span>
                </div>

                @if(isset($skinWarning['tips']) && is_array($skinWarning['tips']) && count($skinWarning['tips']) > 0)
                    <div class="mt-auto pt-4 border-t border-gray-100 dark:border-white/10">
                        <div class="flex items-center gap-2 mb-4 text-sm font-semibold text-gray-900 dark:text-white">
                            <x-filament::icon icon="heroicon-s-light-bulb" class="w-5 h-5 text-warning-500" />
                            <span>Tindakan Preventif</span>
                        </div>
                        <ul class="space-y-3">
                            @foreach($skinWarning['tips'] as $tip)
                                <li class="flex items-start gap-3">
                                    <div class="mt-1.5 w-1.5 h-1.5 rounded-full shrink-0" style="background-color:rgba(var(--{{ $skinColor }}-500),1);"></div>
                                    <span class="text-sm font-medium text-gray-600 dark:text-gray-300 leading-relaxed">{{ $tip }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
            @endif

            {{-- Sleep Detailed Analysis --}}
            @if(isset($sleepCorrelation))
            <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 flex flex-col h-full">
                <div class="flex items-center gap-x-2 mb-2">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        Reservasi Energi
                    </span>
                </div>
                
                <div class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white mb-3">
                    {{ $sleepCorrelation['title'] ?? '-' }}
                </div>
                
                <div class="fi-color-custom flex items-start gap-2 text-sm font-medium text-custom-600 dark:text-custom-400 mb-6" style="--c-400:var(--{{ $sleepColor }}-400);--c-600:var(--{{ $sleepColor }}-600);">
                    <x-filament::icon icon="{{ $sleepCorrelation['icon'] ?? 'heroicon-s-moon' }}" class="w-5 h-5 shrink-0 mt-0.5" />
                    <span class="leading-relaxed">
                        {{ $sleepCorrelation['message'] ?? 'Memuat analisis tidur...' }}
                    </span>
                </div>

                @if(isset($sleepCorrelation['tips']) && is_array($sleepCorrelation['tips']) && count($sleepCorrelation['tips']) > 0)
                    <div class="mt-auto pt-4 border-t border-gray-100 dark:border-white/10">
                        <div class="flex items-center gap-2 mb-4 text-sm font-semibold text-gray-900 dark:text-white">
                            <x-filament::icon icon="heroicon-s-bolt" class="w-5 h-5 text-warning-500" />
                            <span>Saran Produktivitas</span>
                        </div>
                        <ul class="space-y-3">
                            @foreach($sleepCorrelation['tips'] as $tip)
                                <li class="flex items-start gap-3">
                                    <div class="mt-1.5 w-1.5 h-1.5 rounded-full shrink-0" style="background-color:rgba(var(--{{ $sleepColor }}-500),1);"></div>
                                    <span class="text-sm font-medium text-gray-600 dark:text-gray-300 leading-relaxed">{{ $tip }}</span>
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
