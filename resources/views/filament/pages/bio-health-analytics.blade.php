<x-filament-panels::page>
    @if($activeTab === 'analytics')
        <div class="mt-8 grid grid-cols-1 xl:grid-cols-2 gap-6">
            
            {{-- Skin / Weather Detailed Analysis --}}
            @if(isset($skinWarning))
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2" style="color: rgba(var(--{{ $skinWarning['color'] ?? 'primary' }}-500), 1);">
                        <x-filament::icon icon="{{ $skinWarning['icon'] ?? 'heroicon-o-face-smile' }}" class="w-6 h-6" />
                        <span class="text-gray-900 dark:text-white">Kondisi Lingkungan: {{ $skinWarning['title'] ?? '-' }}</span>
                    </div>
                </x-slot>

                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                    {{ $skinWarning['message'] ?? 'Memuat analisis kulit...' }}
                </p>

                @if(isset($skinWarning['tips']) && is_array($skinWarning['tips']) && count($skinWarning['tips']) > 0)
                    <div class="mt-6 pt-4 border-t border-gray-200 dark:border-white/10">
                        <h4 class="font-bold text-sm mb-3 text-gray-800 dark:text-gray-200">Tindakan Preventif</h4>
                        <ul class="space-y-3">
                            @foreach($skinWarning['tips'] as $tip)
                                <li class="flex items-start gap-3">
                                    <x-filament::icon icon="heroicon-m-check-circle" class="w-5 h-5 shrink-0" style="color: rgba(var(--{{ $skinWarning['color'] ?? 'primary' }}-500), 1);" />
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $tip }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </x-filament::section>
            @endif

            {{-- Sleep Detailed Analysis --}}
            @if(isset($sleepCorrelation))
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2" style="color: rgba(var(--{{ $sleepCorrelation['color'] ?? 'primary' }}-500), 1);">
                        <x-filament::icon icon="{{ $sleepCorrelation['icon'] ?? 'heroicon-o-moon' }}" class="w-6 h-6" />
                        <span class="text-gray-900 dark:text-white">Reservasi Energi: {{ $sleepCorrelation['title'] ?? '-' }}</span>
                    </div>
                </x-slot>

                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                    {{ $sleepCorrelation['message'] ?? 'Memuat analisis tidur...' }}
                </p>

                @if(isset($sleepCorrelation['tips']) && is_array($sleepCorrelation['tips']) && count($sleepCorrelation['tips']) > 0)
                    <div class="mt-6 pt-4 border-t border-gray-200 dark:border-white/10">
                        <h4 class="font-bold text-sm mb-3 text-gray-800 dark:text-gray-200">Saran Produktivitas</h4>
                        <ul class="space-y-3">
                            @foreach($sleepCorrelation['tips'] as $tip)
                                <li class="flex items-start gap-3">
                                    <x-filament::icon icon="heroicon-m-check-circle" class="w-5 h-5 shrink-0" style="color: rgba(var(--{{ $sleepCorrelation['color'] ?? 'primary' }}-500), 1);" />
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $tip }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </x-filament::section>
            @endif

        </div>
    @elseif($activeTab === 'log_tidur')
        <div class="mt-4">
            {{ $this->table }}
        </div>
    @endif
</x-filament-panels::page>
