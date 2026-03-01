<x-filament-panels::page>
    <div class="fi-section rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 bg-gradient-to-r from-violet-600 to-indigo-600 p-6 text-white mb-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xl font-bold tracking-tight">🔐 Backup Data Solara</p>
                <p class="mt-1 text-sm opacity-80">Terenkripsi AES-256-CBC • Format <code class="fi-badge rounded-md px-1 bg-white/20">.slr</code> eksklusif</p>
                <p class="mt-3 text-sm opacity-90 max-w-xl">
                    Semua data personal Anda dikemas &amp; dienkripsi. File hanya bisa dibaca oleh sistem Solara dengan akun Anda sendiri.
                </p>
            </div>
            <div class="fi-section rounded-lg bg-white/20 backdrop-blur px-5 py-3 text-center min-w-[130px]">
                <p class="text-3xl font-extrabold tabular-nums">{{ array_sum($this->backupStats) }}</p>
                <p class="text-xs opacity-80 mt-0.5">Total Record</p>
                @if($this->lastBackupAt)
                    <div class="border-t border-white/30 mt-2 pt-2">
                        <p class="text-xs opacity-70">Terakhir Backup</p>
                        <p class="text-sm font-semibold">{{ \Carbon\Carbon::parse($this->lastBackupAt)->locale('id')->diffForHumans() }}</p>
                    </div>
                @else
                    <div class="border-t border-white/30 mt-2 pt-2">
                        <p class="text-xs opacity-60 italic">Belum pernah backup</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Stats Grid --}}
    <x-filament::section>
        <x-slot name="heading">📊 Ringkasan Data</x-slot>
        <x-slot name="description">Data yang akan dimasukkan ke dalam file backup</x-slot>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach([
                ['Tasks',           '✅', $this->backupStats['tasks']],
                ['Habits',          '🔄', $this->backupStats['habits']],
                ['Habit Logs',      '📒', $this->backupStats['habit_logs']],
                ['Catatan',         '🗒️', $this->backupStats['notes']],
                ['Jadwal Kuliah',   '🎓', $this->backupStats['schedules']],
                ['Tugas Kuliah',    '📚', $this->backupStats['assignments']],
                ['Transaksi',       '💰', $this->backupStats['finances']],
                ['Goals',           '🎯', $this->backupStats['goals']],
            ] as [$label, $icon, $count])
            <div class="fi-section rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10 bg-white dark:bg-gray-900 px-4 py-3 flex items-center gap-3 shadow-sm">
                <span class="text-2xl leading-none">{{ $icon }}</span>
                <div>
                    <p class="text-lg font-bold tabular-nums leading-none text-gray-900 dark:text-white">{{ number_format($count) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $label }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </x-filament::section>

    {{-- Info Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-filament::section>
            <x-slot name="heading">✅ Yang Termasuk Backup</x-slot>
            <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                @foreach([
                    ['✅','Tasks & to-do list personal'],
                    ['🔄','Habits beserta log penyelesaian'],
                    ['🗒️','Catatan (Notes)'],
                    ['🎓','Jadwal & tugas kuliah'],
                    ['💰','Transaksi keuangan'],
                    ['🎯','Goals & milestones'],
                ] as [$ic,$label])
                <li class="flex items-center gap-2"><span>{{ $ic }}</span> {{ $label }}</li>
                @endforeach
            </ul>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">🔐 Tentang Format .slr</x-slot>
            <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                @foreach([
                    ['📁','Format biner eksklusif Solara'],
                    ['🔑','Enkripsi AES-256-CBC per-akun'],
                    ['🚫','Tidak bisa dibuka teks editor biasa'],
                    ['🛡️','Kunci unik: APP_KEY + User ID'],
                    ['💾','Simpan di Google Drive / tempat aman'],
                ] as [$ic,$label])
                <li class="flex items-center gap-2"><span>{{ $ic }}</span> {{ $label }}</li>
                @endforeach
            </ul>
        </x-filament::section>
    </div>

    {{-- Backup History --}}
    <x-filament::section>
        <x-slot name="heading">🕓 Riwayat Backup</x-slot>
        <x-slot name="description">Log backup yang pernah Anda lakukan (maks. 20 terakhir)</x-slot>

        @if(empty($this->backupHistory))
            <div class="py-10 text-center">
                <p class="text-4xl mb-3">📭</p>
                <p class="font-medium text-gray-600 dark:text-gray-400">Belum ada riwayat backup</p>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Klik "Download Backup Sekarang" di pojok kanan atas untuk memulai.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-sm dark:divide-white/5">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-white/5">
                            <th class="fi-ta-header-cell px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">#</th>
                            <th class="fi-ta-header-cell px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Waktu Backup</th>
                            <th class="fi-ta-header-cell px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Nama File</th>
                            <th class="fi-ta-header-cell px-3 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Ukuran</th>
                            <th class="fi-ta-header-cell px-3 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Record</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach($this->backupHistory as $i => $log)
                        @php
                            $dt = \Carbon\Carbon::parse($log['created_at'])->locale('id');
                            $kb = round($log['size_bytes'] / 1024, 1);
                        @endphp
                        <tr class="{{ $i === 0 ? 'bg-violet-50 dark:bg-violet-950/20' : 'bg-white dark:bg-gray-900' }} hover:bg-gray-50 dark:hover:bg-white/5 transition">
                            <td class="fi-ta-cell px-3 py-3 text-gray-400 dark:text-gray-500 whitespace-nowrap">
                                {{ $i + 1 }}
                                @if($i === 0)
                                    <x-filament::badge color="violet" size="xs" class="ml-1">Terbaru</x-filament::badge>
                                @endif
                            </td>
                            <td class="fi-ta-cell px-3 py-3 whitespace-nowrap">
                                <p class="font-medium text-gray-900 dark:text-white">{{ $dt->isoFormat('D MMM YYYY, HH:mm') }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ $dt->diffForHumans() }}</p>
                            </td>
                            <td class="fi-ta-cell px-3 py-3">
                                <code class="rounded bg-gray-100 dark:bg-gray-800 px-2 py-0.5 text-xs text-gray-700 dark:text-gray-300">{{ $log['filename'] }}</code>
                            </td>
                            <td class="fi-ta-cell px-3 py-3 text-right text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ $kb }} KB</td>
                            <td class="fi-ta-cell px-3 py-3 text-right font-medium text-gray-700 dark:text-gray-200 whitespace-nowrap">{{ number_format($log['total_records']) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
