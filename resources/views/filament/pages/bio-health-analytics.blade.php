<x-filament-panels::page>
    @if($activeTab === 'analytics')
        @php
            $skinColor = $skinWarning['color'] ?? 'primary';
            $sleepColor = $sleepCorrelation['color'] ?? 'primary';
        @endphp
        <div class="mt-8 grid grid-cols-1 xl:grid-cols-2 gap-8">
            
            {{-- Skin / Weather Detailed Analysis --}}
            @if(isset($skinWarning))
            <div class="relative overflow-hidden bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-[2rem] shadow-sm">
                <div class="absolute -top-10 -right-10 opacity-5 pointer-events-none">
                    <x-filament::icon icon="heroicon-s-sparkles" class="w-64 h-64 text-{{ $skinColor }}-500" />
                </div>
                
                <div class="p-8 relative z-10 flex flex-col h-full">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="p-3.5 bg-{{ $skinColor }}-50 dark:bg-{{ $skinColor }}-500/10 rounded-2xl shadow-inner-sm">
                            <x-filament::icon icon="{{ $skinWarning['icon'] ?? 'heroicon-o-face-smile' }}" class="w-7 h-7 text-{{ $skinColor }}-600 dark:text-{{ $skinColor }}-400" />
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 tracking-tight">Kondisi Lingkungan</h3>
                            <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-{{ $skinColor }}-100 dark:bg-{{ $skinColor }}-500/20 text-{{ $skinColor }}-700 dark:text-{{ $skinColor }}-400 mt-1 uppercase tracking-widest">
                                {{ $skinWarning['title'] ?? '-' }}
                            </div>
                        </div>
                    </div>

                    <p class="text-[15px] text-gray-600 dark:text-gray-400 leading-relaxed font-medium mb-8">
                        {{ $skinWarning['message'] ?? 'Memuat analisis kulit...' }}
                    </p>

                    @if(isset($skinWarning['tips']) && is_array($skinWarning['tips']) && count($skinWarning['tips']) > 0)
                        <div class="mt-auto">
                            <h4 class="font-bold text-[11px] uppercase text-gray-400 dark:text-gray-500 tracking-[0.2em] mb-4">Tindakan Preventif</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @foreach($skinWarning['tips'] as $tip)
                                    <div class="flex items-center gap-3 p-3.5 bg-gray-50 hover:bg-gray-100 dark:bg-gray-800/50 dark:hover:bg-gray-800 transition-colors rounded-2xl border border-gray-100 dark:border-gray-800 shrink-0 h-full">
                                        <div class="flex-none rounded-full p-1 bg-white dark:bg-gray-700 shadow-sm text-{{ $skinColor }}-500 dark:text-{{ $skinColor }}-400">
                                            <x-filament::icon icon="heroicon-m-check" class="w-3.5 h-3.5" />
                                        </div>
                                        <span class="text-[13px] font-semibold text-gray-700 dark:text-gray-300">{{ $tip }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Sleep Detailed Analysis --}}
            @if(isset($sleepCorrelation))
            <div class="relative overflow-hidden bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-[2rem] shadow-sm">
                <!-- Decorative Background -->
                <div class="absolute -top-10 -right-10 opacity-5 pointer-events-none">
                    <x-filament::icon icon="heroicon-s-bolt" class="w-64 h-64 text-{{ $sleepColor }}-500" />
                </div>
                
                <div class="p-8 relative z-10 flex flex-col h-full">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="p-3.5 bg-{{ $sleepColor }}-50 dark:bg-{{ $sleepColor }}-500/10 rounded-2xl shadow-inner-sm">
                            <x-filament::icon icon="{{ $sleepCorrelation['icon'] ?? 'heroicon-o-moon' }}" class="w-7 h-7 text-{{ $sleepColor }}-600 dark:text-{{ $sleepColor }}-400" />
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 tracking-tight">Reservasi Energi</h3>
                            <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-{{ $sleepColor }}-100 dark:bg-{{ $sleepColor }}-500/20 text-{{ $sleepColor }}-700 dark:text-{{ $sleepColor }}-400 mt-1 uppercase tracking-widest">
                                {{ $sleepCorrelation['title'] ?? '-' }}
                            </div>
                        </div>
                    </div>

                    <p class="text-[15px] text-gray-600 dark:text-gray-400 leading-relaxed font-medium mb-8">
                        {{ $sleepCorrelation['message'] ?? 'Memuat analisis tidur...' }}
                    </p>

                    @if(isset($sleepCorrelation['tips']) && is_array($sleepCorrelation['tips']) && count($sleepCorrelation['tips']) > 0)
                        <div class="mt-auto">
                            <h4 class="font-bold text-[11px] uppercase text-gray-400 dark:text-gray-500 tracking-[0.2em] mb-4">Saran Produktivitas</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @foreach($sleepCorrelation['tips'] as $tip)
                                    <div class="flex items-center gap-3 p-3.5 bg-gray-50 hover:bg-gray-100 dark:bg-gray-800/50 dark:hover:bg-gray-800 transition-colors rounded-2xl border border-gray-100 dark:border-gray-800 shrink-0 h-full">
                                        <div class="flex-none rounded-full p-1 bg-white dark:bg-gray-700 shadow-sm text-{{ $sleepColor }}-500 dark:text-{{ $sleepColor }}-400">
                                            <x-filament::icon icon="heroicon-m-chevron-right" class="w-3.5 h-3.5" />
                                        </div>
                                        <span class="text-[13px] font-semibold text-gray-700 dark:text-gray-300">{{ $tip }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            @endif

        </div>
    @elseif($activeTab === 'log_tidur')
        <div class="mt-4">
            {{ $this->table }}
        </div>
    @endif
</x-filament-panels::page>
