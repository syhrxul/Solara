<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <x-filament::icon icon="heroicon-s-heart" class="w-5 h-5 text-rose-500" />
                Aktivitas Google Fit
            </h3>
        </div>

        @if($isConnected)
            <div class="grid grid-cols-2 gap-4">
                <div class="p-4 rounded-xl bg-blue-50 dark:bg-blue-900/30 border border-blue-100 dark:border-blue-800">
                    <p class="text-xs font-medium text-blue-600 dark:text-blue-400 mb-1 flex items-center gap-1">
                        <x-filament::icon icon="heroicon-o-fire" class="w-4 h-4" /> Langkah Hari Ini
                    </p>
                    <p class="text-2xl font-black text-blue-700 dark:text-blue-300">
                        {{ number_format($steps) }}
                    </p>
                </div>
                <div class="p-4 rounded-xl bg-orange-50 dark:bg-orange-900/30 border border-orange-100 dark:border-orange-800">
                    <p class="text-xs font-medium text-orange-600 dark:text-orange-400 mb-1 flex items-center gap-1">
                        <x-filament::icon icon="heroicon-o-bolt" class="w-4 h-4" /> Kalori Terbakar
                    </p>
                    <p class="text-2xl font-black text-orange-700 dark:text-orange-300">
                        {{ number_format($calories) }} <span class="text-sm font-medium">kcal</span>
                    </p>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-4 flex items-center gap-1">
                <x-filament::icon icon="heroicon-s-check-circle" class="w-3 h-3 text-green-500" /> Tersinkronisasi
            </p>
        @else
            <div class="text-center py-6">
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Hubungkan akun Anda dengan Google Fit untuk melacak langkah dan kalori harian langsung dari Dashboard.
                </p>
                <!-- Tombol ini sengaja diarahkan ke http://localhost:8000 sementara -->
                <a href="http://localhost:8000/auth/google/redirect" target="_self" class="inline-flex items-center justify-center px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white text-sm font-semibold rounded-lg shadow-md focus:outline-none transition-colors">
                    Hubungkan ke Google Fit
                </a>
                <p class="text-xs text-gray-400 mt-2 font-mono bg-gray-100 dark:bg-gray-800 p-2 rounded">
                    Wajib Nyalakan: php artisan serve
                </p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
