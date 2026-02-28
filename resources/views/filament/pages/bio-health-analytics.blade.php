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
                <!-- Stat Header -->
                <div class="flex items-center gap-x-2 text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">
                    <x-filament::icon icon="{{ $skinWarning['icon'] ?? 'heroicon-o-face-smile' }}" class="h-5 w-5" />
                    <span>Kondisi Lingkungan</span>
                </div>
                
                <!-- Stat Title (Big Text) -->
                <div class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white mb-2">
                    {{ $skinWarning['title'] ?? '-' }}
                </div>
                
                <!-- Stat Message (Colored - using custom inline styles to force tailwind colors) -->
                <div class="text-sm font-medium mb-4" style="color: rgba(var(--{{ $skinColor }}-500), 1);">
                    {{ $skinWarning['message'] ?? 'Memuat analisis kulit...' }}
                </div>

                <!-- Tips Section -->
                @if(isset($skinWarning['tips']) && is_array($skinWarning['tips']) && count($skinWarning['tips']) > 0)
                    <div class="mt-auto">
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Tindakan Preventif
                        </div>
                        <ul class="space-y-2">
                            @foreach($skinWarning['tips'] as $tip)
                                <li class="flex items-start gap-2">
                                    <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 shrink-0" style="color: rgba(var(--{{ $skinColor }}-500), 1);" />
                                    <span class="text-sm font-medium" style="color: rgba(var(--{{ $skinColor }}-500), 1);">{{ $tip }}</span>
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
                <!-- Stat Header -->
                <div class="flex items-center gap-x-2 text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">
                    <x-filament::icon icon="{{ $sleepCorrelation['icon'] ?? 'heroicon-o-moon' }}" class="h-5 w-5" />
                    <span>Reservasi Energi</span>
                </div>
                
                <!-- Stat Title (Big Text) -->
                <div class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white mb-2">
                    {{ $sleepCorrelation['title'] ?? '-' }}
                </div>
                
                <!-- Stat Message (Colored - using custom inline styles to force tailwind colors) -->
                <div class="text-sm font-medium mb-4" style="color: rgba(var(--{{ $sleepColor }}-500), 1);">
                    {{ $sleepCorrelation['message'] ?? 'Memuat analisis tidur...' }}
                </div>

                <!-- Tips Section -->
                @if(isset($sleepCorrelation['tips']) && is_array($sleepCorrelation['tips']) && count($sleepCorrelation['tips']) > 0)
                    <div class="mt-auto">
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Saran Produktivitas
                        </div>
                        <ul class="space-y-2">
                            @foreach($sleepCorrelation['tips'] as $tip)
                                <li class="flex items-start gap-2">
                                    <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 shrink-0" style="color: rgba(var(--{{ $sleepColor }}-500), 1);" />
                                    <span class="text-sm font-medium" style="color: rgba(var(--{{ $sleepColor }}-500), 1);">{{ $tip }}</span>
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
