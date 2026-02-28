<x-filament-panels::page>
    @if($activeTab === 'analytics')
        @php
            $skinColor = $skinWarning['color'] ?? 'primary';
            $sleepColor = $sleepCorrelation['color'] ?? 'primary';
        @endphp
            {{-- Detailed Analysis (Using Filament Native Stats Widget) --}}
            <div class="mt-8">
                @livewire(\App\Filament\Pages\Widgets\BioHealthDetailedStats::class, [
                    'skinWarning' => $skinWarning,
                    'sleepCorrelation' => $sleepCorrelation
                ])
            </div>
    @elseif($activeTab === 'log_tidur')
        <div class="mt-4">
            {{ $this->table }}
        </div>
    @endif
</x-filament-panels::page>
