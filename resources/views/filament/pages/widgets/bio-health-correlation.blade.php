<x-filament-widgets::widget>
    <x-filament::section class="mb-4 text-white !bg-slate-900 border-none relative overflow-hidden">
        <!-- Background glow effects -->
        <div class="absolute -top-10 -right-10 w-40 h-40 bg-orange-500/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-blue-500/20 rounded-full blur-3xl"></div>

        <div class="relative z-10 flex items-center justify-between gap-4 mb-6">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 shrink-0 rounded-full bg-indigo-500/20 flex items-center justify-center text-indigo-400">
                    <x-heroicon-o-sparkles class="!w-5 !h-5" />
                </div>
                <div>
                    <h2 class="text-xl font-bold tracking-tight">Bio-Health Analytics</h2>
                    <p class="text-sm text-slate-400">Personalized correlation for your day.</p>
                </div>
            </div>
            @if($weather)
                <div class="hidden sm:flex items-center gap-3 px-4 py-2 bg-white/5 rounded-full border border-white/10 text-sm shadow-inner backdrop-blur-md">
                    <div class="flex items-center gap-1.5 text-slate-300"><x-heroicon-o-map-pin class="!w-4 !h-4 text-emerald-400"/> {!! $weather['location'] ?? '-' !!}</div>
                    <div class="w-px h-4 bg-white/20"></div>
                    <div class="flex items-center gap-1.5"><span class="text-orange-400"><x-heroicon-o-sun class="!w-4 !h-4"/></span> {!! $weather['temperature'] ?? '-' !!}°C</div>
                    <div class="w-px h-4 bg-white/20"></div>
                    <div class="flex items-center gap-1.5"><span class="text-purple-400">UV:</span> {!! $weather['uv_index'] ?? '-' !!}</div>
                </div>
            @endif
        </div>

        <div class="relative z-10 grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Weather & Skin -->
            <div class="p-5 rounded-2xl bg-gradient-to-br from-orange-500/10 to-red-500/10 border border-orange-500/30 backdrop-blur shadow-sm hover:shadow-orange-500/10 transition-shadow">
                <div class="flex items-center gap-3 mb-3 text-orange-400">
                    <x-heroicon-o-fire class="!w-5 !h-5 shrink-0" />
                    <h3 class="font-semibold text-lg text-orange-100">Weather & Skin Check</h3>
                </div>
                <p class="text-[15px] leading-relaxed text-slate-300 font-medium">
                    {{ $skinWarning }}
                </p>
            </div>

            <!-- Sleep & Productivity -->
            <div class="p-5 rounded-2xl bg-gradient-to-br from-blue-500/10 to-indigo-500/10 border border-blue-500/30 backdrop-blur shadow-sm hover:shadow-blue-500/10 transition-shadow">
                <div class="flex items-center gap-3 mb-3 text-blue-400">
                    <x-heroicon-o-moon class="!w-5 !h-5 shrink-0" />
                    <h3 class="font-semibold text-lg text-blue-100">Sleep & Productivity</h3>
                </div>
                <p class="text-[15px] leading-relaxed text-slate-300 font-medium">
                    {{ $sleepCorrelation }}
                </p>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
