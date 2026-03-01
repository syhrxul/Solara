<x-filament-panels::page>
    @php
        $stats = [
            ['label' => 'Tasks',          'icon' => '✅', 'count' => $this->backupStats['tasks'],       'color' => 'emerald'],
            ['label' => 'Habits',         'icon' => '🔄', 'count' => $this->backupStats['habits'],      'color' => 'violet'],
            ['label' => 'Habit Logs',     'icon' => '📒', 'count' => $this->backupStats['habit_logs'],  'color' => 'indigo'],
            ['label' => 'Catatan',        'icon' => '🗒️', 'count' => $this->backupStats['notes'],       'color' => 'amber'],
            ['label' => 'Jadwal Kuliah',  'icon' => '🎓', 'count' => $this->backupStats['schedules'],   'color' => 'sky'],
            ['label' => 'Tugas Kuliah',   'icon' => '📚', 'count' => $this->backupStats['assignments'], 'color' => 'blue'],
            ['label' => 'Transaksi',      'icon' => '💰', 'count' => $this->backupStats['finances'],    'color' => 'green'],
            ['label' => 'Goals',          'icon' => '🎯', 'count' => $this->backupStats['goals'],       'color' => 'rose'],
        ];
        $total = array_sum(array_column($stats, 'count'));
    @endphp

    <div class="space-y-8">

        {{-- === HERO SECTION === --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-violet-600 via-purple-600 to-indigo-700 p-6 text-white shadow-xl">
            {{-- Background pattern --}}
            <div class="pointer-events-none absolute inset-0 opacity-10">
                <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
                    <defs><pattern id="grid" width="32" height="32" patternUnits="userSpaceOnUse">
                        <path d="M 32 0 L 0 0 0 32" fill="none" stroke="white" stroke-width="0.5"/>
                    </pattern></defs>
                    <rect width="100%" height="100%" fill="url(#grid)"/>
                </svg>
            </div>

            <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/20 text-2xl backdrop-blur">🔐</div>
                        <div>
                            <h2 class="text-xl font-bold">Backup Data Solara</h2>
                            <p class="text-sm text-violet-200">Terenkripsi AES-256-CBC • Format .slr eksklusif</p>
                        </div>
                    </div>
                    <p class="mt-3 max-w-lg text-sm text-violet-100">
                        Seluruh data personal Anda dikemas dan dienkripsi secara otomatis ke dalam file <code class="rounded bg-white/20 px-1 font-mono">.slr</code>.
                        File ini hanya dapat dibaca oleh sistem Solara dengan akun Anda.
                    </p>
                </div>

                <div class="flex-shrink-0 rounded-xl bg-white/15 p-4 text-center backdrop-blur">
                    <p class="text-3xl font-extrabold">{{ number_format($total) }}</p>
                    <p class="text-xs text-violet-200">Total Record</p>

                    @if($this->lastBackupAt)
                        <div class="mt-2 border-t border-white/20 pt-2">
                            <p class="text-xs text-violet-200">Backup Terakhir</p>
                            <p class="text-sm font-semibold">{{ \Carbon\Carbon::parse($this->lastBackupAt)->locale('id')->diffForHumans() }}</p>
                        </div>
                    @else
                        <div class="mt-2 border-t border-white/20 pt-2">
                            <p class="text-xs text-violet-300 italic">Belum pernah backup</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- === DATA STATS GRID === --}}
        <div>
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">📊 Ringkasan Data yang Akan Dibackup</h3>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach($stats as $stat)
                <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-3 shadow-sm transition hover:shadow-md dark:border-gray-700 dark:bg-gray-900">
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-gray-100 text-lg dark:bg-gray-800">
                        {{ $stat['icon'] }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-xl font-bold text-gray-800 dark:text-white">{{ number_format($stat['count']) }}</p>
                        <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- === INFO CARDS === --}}
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-800 dark:bg-emerald-950/40">
                <div class="flex items-center gap-2 mb-3">
                    <span class="text-lg">✅</span>
                    <h4 class="font-semibold text-emerald-800 dark:text-emerald-300">Yang Termasuk dalam Backup</h4>
                </div>
                <ul class="space-y-1.5 text-sm text-emerald-700 dark:text-emerald-400">
                    @foreach([['✅','Tasks & to-do list'],['🔄','Habits & log penyelesaian'],['🗒️','Catatan (Notes)'],['🎓','Jadwal & tugas kuliah'],['💰','Transaksi keuangan'],['🎯','Goals & milestones']] as [$ic,$label])
                    <li class="flex items-center gap-2"><span>{{ $ic }}</span> {{ $label }}</li>
                    @endforeach
                </ul>
            </div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-800 dark:bg-amber-950/40">
                <div class="flex items-center gap-2 mb-3">
                    <span class="text-lg">🔐</span>
                    <h4 class="font-semibold text-amber-800 dark:text-amber-300">Tentang Format .slr</h4>
                </div>
                <ul class="space-y-1.5 text-sm text-amber-700 dark:text-amber-400">
                    @foreach([['�','Format biner eksklusif Solara'],['🔑','Enkripsi AES-256-CBC per-akun'],['🚫','Tidak bisa dibaca text editor'],['🛡️','Kunci unik: APP_KEY + user ID'],['💾','Simpan di tempat yang aman']] as [$ic,$label])
                    <li class="flex items-center gap-2"><span>{{ $ic }}</span> {{ $label }}</li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- === BACKUP HISTORY LOG === --}}
        <div>
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">🕓 Riwayat Backup</h3>

            @if(empty($this->backupHistory))
                <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 bg-gray-50 py-10 text-center dark:border-gray-700 dark:bg-gray-900">
                    <p class="text-3xl mb-2">📭</p>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Belum ada riwayat backup</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Klik "Download Backup Sekarang" untuk memulai backup pertama Anda.</p>
                </div>
            @else
                <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Waktu Backup</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Nama File</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Ukuran</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Record</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-800 dark:bg-gray-900">
                            @foreach($this->backupHistory as $i => $log)
                            @php
                                $dt     = \Carbon\Carbon::parse($log['created_at'])->locale('id');
                                $sizeKb = round($log['size_bytes'] / 1024, 1);
                            @endphp
                            <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-800 {{ $i === 0 ? 'bg-violet-50/50 dark:bg-violet-950/20' : '' }}">
                                <td class="px-4 py-3 text-sm text-gray-400 dark:text-gray-500">
                                    {{ $i + 1 }}
                                    @if($i === 0)<span class="ml-1 rounded-full bg-violet-100 px-1.5 py-0.5 text-xs font-medium text-violet-700 dark:bg-violet-900 dark:text-violet-300">Terbaru</span>@endif
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $dt->isoFormat('D MMM YYYY, HH:mm') }}</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ $dt->diffForHumans() }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <code class="rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ $log['filename'] }}</code>
                                </td>
                                <td class="px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-300">{{ $sizeKb }} KB</td>
                                <td class="px-4 py-3 text-right text-sm font-medium text-gray-700 dark:text-gray-200">{{ number_format($log['total_records']) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="mt-2 text-xs text-gray-400 dark:text-gray-500 text-right">Menampilkan {{ count($this->backupHistory) }} riwayat terakhir (maks. 20)</p>
            @endif
        </div>

    </div>
</x-filament-panels::page>
