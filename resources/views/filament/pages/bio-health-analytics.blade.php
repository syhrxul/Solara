<x-filament-panels::page>
    <x-filament::section class="text-white !bg-slate-900 border-none relative overflow-hidden">
        <!-- Background glow effects -->
        <div class="absolute -top-10 -right-10 w-40 h-40 bg-orange-500/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-blue-500/20 rounded-full blur-3xl"></div>

        <div class="relative z-10 flex items-center justify-between gap-4 mb-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 shrink-0 rounded-full bg-indigo-500/20 flex items-center justify-center text-indigo-400">
                    <x-heroicon-o-sparkles style="width: 1.5rem; height: 1.5rem;" />
                </div>
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">Bio-Health Analytics</h2>
                    <p class="text-sm text-slate-400">Personalized correlation for your day.</p>
                </div>
            </div>
            @if($weather)
                <div class="hidden sm:flex items-center gap-4 px-5 py-2.5 bg-white/5 rounded-full border border-white/10 shadow-inner backdrop-blur-md">
                    <div class="flex items-center gap-2 text-slate-300 font-medium"><x-heroicon-o-map-pin style="width: 1.25rem; height: 1.25rem;" class="text-emerald-400"/> {!! $weather['location'] ?? '-' !!}</div>
                    <div class="w-px h-5 bg-white/20"></div>
                    <div class="flex items-center gap-2 font-medium"><span class="text-orange-400"><x-heroicon-o-sun style="width: 1.25rem; height: 1.25rem;"/></span> {!! $weather['temperature'] ?? '-' !!}°C</div>
                    <div class="w-px h-5 bg-white/20"></div>
                    <div class="flex items-center gap-2 font-medium"><span class="text-purple-400">UV:</span> {!! $weather['uv_index'] ?? '-' !!}</div>
                </div>
            @endif
        </div>

        @php
            $colorMaps = [
                'emerald' => ['text' => 'text-emerald-400', 'bg' => 'bg-emerald-500/20', 'border' => 'border-emerald-500/30', 'from' => 'from-emerald-500/10', 'hover' => 'hover:shadow-emerald-500/10', 'icon' => 'text-emerald-500', 'header' => 'text-emerald-100'],
                'red' => ['text' => 'text-red-400', 'bg' => 'bg-red-500/20', 'border' => 'border-red-500/30', 'from' => 'from-red-500/10', 'hover' => 'hover:shadow-red-500/10', 'icon' => 'text-red-500', 'header' => 'text-red-100'],
                'orange' => ['text' => 'text-orange-400', 'bg' => 'bg-orange-500/20', 'border' => 'border-orange-500/30', 'from' => 'from-orange-500/10', 'hover' => 'hover:shadow-orange-500/10', 'icon' => 'text-orange-500', 'header' => 'text-orange-100'],
                'blue' => ['text' => 'text-blue-400', 'bg' => 'bg-blue-500/20', 'border' => 'border-blue-500/30', 'from' => 'from-blue-500/10', 'hover' => 'hover:shadow-blue-500/10', 'icon' => 'text-blue-500', 'header' => 'text-blue-100'],
                'slate' => ['text' => 'text-slate-400', 'bg' => 'bg-slate-500/20', 'border' => 'border-slate-500/30', 'from' => 'from-slate-500/10', 'hover' => 'hover:shadow-slate-500/10', 'icon' => 'text-slate-500', 'header' => 'text-slate-100'],
            ];
            
            $skinColors = $colorMaps[$skinWarning['color'] ?? 'slate'];
            $sleepColors = $colorMaps[$sleepCorrelation['color'] ?? 'slate'];
        @endphp

        <div class="relative z-10 grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Weather & Skin -->
            <div class="p-6 rounded-2xl bg-gradient-to-br {{ $skinColors['from'] }} to-slate-900/50 border {{ $skinColors['border'] }} backdrop-blur shadow-sm {{ $skinColors['hover'] }} transition-all duration-300">
                <div class="flex items-center gap-4 mb-5 {{ $skinColors['text'] }}">
                    <div class="w-12 h-12 shrink-0 rounded-xl {{ $skinColors['bg'] }} flex items-center justify-center border {{ $skinColors['border'] }}">
                        @svg($skinWarning['icon'], 'w-6 h-6 shrink-0')
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-widest font-bold opacity-80 mb-0.5">Weather & Skin Check</p>
                        <h3 class="font-bold text-lg leading-tight {{ $skinColors['header'] }}">{{ $skinWarning['title'] }}</h3>
                    </div>
                </div>
                <p class="text-[15px] leading-relaxed text-slate-300 mb-5">
                    {{ $skinWarning['message'] }}
                </p>
                @if(isset($skinWarning['tips']) && count($skinWarning['tips']) > 0)
                    <div class="bg-black/40 rounded-xl p-4 border border-white/5">
                        <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                            <x-heroicon-o-light-bulb class="w-4 h-4 text-amber-400" />
                            Actionable Tips
                        </h4>
                        <ul class="space-y-2.5">
                            @foreach($skinWarning['tips'] as $tip)
                                <li class="flex items-start gap-3 text-sm text-slate-200">
                                    <x-heroicon-s-check-circle class="w-5 h-5 {{ $skinColors['icon'] }} shrink-0 mt-0.5" />
                                    <span class="leading-relaxed">{{ $tip }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <!-- Sleep & Productivity -->
            <div class="p-6 rounded-2xl bg-gradient-to-br {{ $sleepColors['from'] }} to-slate-900/50 border {{ $sleepColors['border'] }} backdrop-blur shadow-sm {{ $sleepColors['hover'] }} transition-all duration-300">
                <div class="flex items-center gap-4 mb-5 {{ $sleepColors['text'] }}">
                    <div class="w-12 h-12 shrink-0 rounded-xl {{ $sleepColors['bg'] }} flex items-center justify-center border {{ $sleepColors['border'] }}">
                        @svg($sleepCorrelation['icon'], 'w-6 h-6 shrink-0')
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-widest font-bold opacity-80 mb-0.5">Sleep & Productivity</p>
                        <h3 class="font-bold text-lg leading-tight {{ $sleepColors['header'] }}">{{ $sleepCorrelation['title'] }}</h3>
                    </div>
                </div>
                <p class="text-[15px] leading-relaxed text-slate-300 mb-5">
                    {{ $sleepCorrelation['message'] }}
                </p>
                @if(isset($sleepCorrelation['tips']) && count($sleepCorrelation['tips']) > 0)
                    <div class="bg-black/40 rounded-xl p-4 border border-white/5">
                        <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                            <x-heroicon-o-light-bulb class="w-4 h-4 text-amber-400" />
                            Actionable Tips
                        </h4>
                        <ul class="space-y-2.5">
                            @foreach($sleepCorrelation['tips'] as $tip)
                                <li class="flex items-start gap-3 text-sm text-slate-200">
                                    <x-heroicon-s-check-circle class="w-5 h-5 {{ $sleepColors['icon'] }} shrink-0 mt-0.5" />
                                    <span class="leading-relaxed">{{ $tip }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
