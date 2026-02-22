<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                🎯 Habit Hari Ini
            </h3>
            <a href="/app/habits" class="text-xs text-indigo-600 dark:text-indigo-400 font-medium hover:underline">Kelola</a>
        </div>
        
        <div class="space-y-3">
            @forelse($habitList as $habit)
                <div class="flex items-center justify-between p-3 rounded-xl border {{ $habit['is_completed'] ? 'border-green-200 dark:border-green-900 bg-green-50 dark:bg-green-900/20' : 'border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900' }}">
                    <div class="flex items-center gap-3">
                        <button 
                            wire:click="toggleHabit({{ $habit['id'] }})"
                            class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition focus:outline-none"
                            style="border-color: {{ $habit['is_completed'] ? $habit['color'] : '#9ca3af' }}; background-color: {{ $habit['is_completed'] ? $habit['color'] : 'transparent' }};"
                        >
                            @if($habit['is_completed'])
                                <x-filament::icon icon="heroicon-s-check" class="w-4 h-4 text-white" />
                            @endif
                        </button>
                        <span class="text-sm font-medium {{ $habit['is_completed'] ? 'line-through text-gray-400 dark:text-gray-500' : 'text-gray-900 dark:text-white' }}">
                            {{ $habit['name'] }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="text-center py-6">
                    <p class="text-sm text-gray-500">Tidak ada habit harian yang aktif.</p>
                </div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
