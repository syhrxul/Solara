<x-filament-panels::page>


    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <x-filament::section>
            <x-slot name="heading">
                Weather & Skin Check
            </x-slot>
            <x-slot name="headerEnd">
                <x-filament::badge color="{{ $skinWarning['color'] ?? 'gray' }}" icon="{{ $skinWarning['icon'] ?? 'heroicon-o-information-circle' }}">
                    {{ $skinWarning['title'] ?? 'N/A' }}
                </x-filament::badge>
            </x-slot>

            <div class="text-gray-600 dark:text-gray-300 mb-4 leading-relaxed">
                {{ $skinWarning['message'] ?? 'Data cuaca tidak tersedia.' }}
            </div>

            @if(isset($skinWarning['tips']) && count($skinWarning['tips']) > 0)
                <div class="mt-4 p-4 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                    <h4 class="font-bold text-sm mb-3 flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-light-bulb" class="w-4 h-4 text-warning-500" />
                        Actionable Tips
                    </h4>
                    <ul class="space-y-2.5">
                        @foreach($skinWarning['tips'] as $tip)
                            <li class="flex items-start gap-3 text-sm text-gray-700 dark:text-gray-300">
                                <x-filament::icon icon="heroicon-s-check-circle" class="w-5 h-5 text-{{ $skinWarning['color'] ?? 'gray' }}-500 shrink-0 mt-0.5" />
                                <span>{{ $tip }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Sleep & Productivity
            </x-slot>
            <x-slot name="headerEnd">
                <x-filament::badge color="{{ $sleepCorrelation['color'] ?? 'gray' }}" icon="{{ $sleepCorrelation['icon'] ?? 'heroicon-o-information-circle' }}">
                    {{ $sleepCorrelation['title'] ?? 'N/A' }}
                </x-filament::badge>
            </x-slot>

            <div class="text-gray-600 dark:text-gray-300 mb-4 leading-relaxed">
                {{ $sleepCorrelation['message'] ?? 'Data tidur tidak tersedia.' }}
            </div>

            @if(isset($sleepCorrelation['tips']) && count($sleepCorrelation['tips']) > 0)
                <div class="mt-4 p-4 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                    <h4 class="font-bold text-sm mb-3 flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-light-bulb" class="w-4 h-4 text-warning-500" />
                        Actionable Tips
                    </h4>
                    <ul class="space-y-2.5">
                        @foreach($sleepCorrelation['tips'] as $tip)
                            <li class="flex items-start gap-3 text-sm text-gray-700 dark:text-gray-300">
                                <x-filament::icon icon="heroicon-s-check-circle" class="w-5 h-5 text-{{ $sleepCorrelation['color'] ?? 'gray' }}-500 shrink-0 mt-0.5" />
                                <span>{{ $tip }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
