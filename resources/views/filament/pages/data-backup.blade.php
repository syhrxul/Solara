<x-filament-panels::page>
    {{-- ============================================================ --}}
    {{-- HERO BANNER --}}
    {{-- ============================================================ --}}
    <div class="fi-section rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 bg-gradient-to-r from-violet-600 to-indigo-600 p-6 text-white">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div style="flex:1;min-width:200px;">
                <p style="font-size:1.2rem;font-weight:700;margin:0;">🔐 Backup Data Solara</p>
                <p style="font-size:0.85rem;opacity:.8;margin-top:4px;">Terenkripsi AES-256-CBC • Format <code style="background:rgba(255,255,255,.2);border-radius:4px;padding:1px 6px;">.slr</code></p>
                <p style="font-size:0.85rem;opacity:.9;margin-top:8px;max-width:500px;line-height:1.5;">
                    Semua data personal Anda dikemas dan dienkripsi otomatis. File hanya bisa dibaca oleh sistem Solara milik akun Anda sendiri.
                </p>
            </div>
            <div style="background:rgba(255,255,255,.15);border-radius:12px;padding:12px 20px;text-align:center;min-width:120px;">
                <p style="font-size:2rem;font-weight:800;margin:0;line-height:1;">{{ array_sum($this->backupStats) }}</p>
                <p style="font-size:0.7rem;opacity:.75;margin-top:2px;">Total Record</p>
                @if($this->lastBackupAt)
                    <div style="border-top:1px solid rgba(255,255,255,.3);margin-top:8px;padding-top:8px;">
                        <p style="font-size:0.7rem;opacity:.7;margin:0;">Terakhir Backup</p>
                        <p style="font-size:0.85rem;font-weight:600;margin:0;">{{ \Carbon\Carbon::parse($this->lastBackupAt)->locale('id')->diffForHumans() }}</p>
                    </div>
                @else
                    <div style="border-top:1px solid rgba(255,255,255,.3);margin-top:8px;padding-top:8px;">
                        <p style="font-size:0.75rem;opacity:.6;font-style:italic;margin:0;">Belum pernah backup</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <x-filament::section>
        <x-slot name="heading">📊 Ringkasan Data</x-slot>
        <x-slot name="description">Data yang akan dimasukkan ke dalam file backup</x-slot>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach([
                ['Tasks',          '✅', $this->backupStats['tasks']],
                ['Habits',         '🔄', $this->backupStats['habits']],
                ['Habit Logs',     '📒', $this->backupStats['habit_logs']],
                ['Catatan',        '🗒️', $this->backupStats['notes']],
                ['Jadwal Kuliah',  '🎓', $this->backupStats['schedules']],
                ['Tugas Kuliah',   '📚', $this->backupStats['assignments']],
                ['Transaksi',      '💰', $this->backupStats['finances']],
                ['Goals',          '🎯', $this->backupStats['goals']],
            ] as [$label, $icon, $count])
            <div class="fi-section rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10 bg-white dark:bg-gray-900 px-4 py-3 flex items-center gap-3 shadow-sm">
                <span style="font-size:1.4rem;line-height:1;">{{ $icon }}</span>
                <div>
                    <p class="text-lg font-bold tabular-nums text-gray-900 dark:text-white" style="margin:0;line-height:1.2;">{{ number_format($count) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400" style="margin:2px 0 0;">{{ $label }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </x-filament::section>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-filament::section>
            <x-slot name="heading">✅ Yang Termasuk Backup</x-slot>
            <ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:8px;">
                @foreach([
                    ['✅','Tasks & to-do list personal'],
                    ['🔄','Habits beserta log penyelesaian'],
                    ['🗒️','Catatan (Notes)'],
                    ['🎓','Jadwal & tugas kuliah'],
                    ['💰','Transaksi keuangan'],
                    ['🎯','Goals & milestones'],
                ] as [$ic, $label])
                <li style="display:flex;align-items:center;gap:8px;font-size:0.875rem;" class="text-gray-600 dark:text-gray-400">
                    <span>{{ $ic }}</span> {{ $label }}
                </li>
                @endforeach
            </ul>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">🔐 Tentang Format .slr</x-slot>
            <ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:8px;">
                @foreach([
                    ['📁','Format biner eksklusif Solara'],
                    ['🔑','Enkripsi AES-256-CBC per-akun'],
                    ['🚫','Tidak bisa dibuka dengan teks editor'],
                    ['🛡️','Kunci unik: APP_KEY + User ID'],
                    ['💾','Simpan di Google Drive / tempat aman'],
                ] as [$ic, $label])
                <li style="display:flex;align-items:center;gap:8px;font-size:0.875rem;" class="text-gray-600 dark:text-gray-400">
                    <span>{{ $ic }}</span> {{ $label }}
                </li>
                @endforeach
            </ul>
        </x-filament::section>
    </div>

    {{-- RESTORE GUIDE --}}
    <x-filament::section>
        <x-slot name="heading">🔁 Cara Restore Data</x-slot>
        <x-slot name="description">Kembalikan data dari file backup .slr yang pernah Anda buat</x-slot>
        <div style="display:flex;flex-direction:column;gap:8px;">
            @foreach([
                ['1','Klik tombol "Restore Backup" di pojok kanan atas'],
                ['2','Upload file .slr yang pernah Anda download'],
                ['3','Pilih mode: Skip (pertahankan data lama) atau Overwrite (timpa data lama)'],
                ['4','Klik "Mulai Restore" — sistem akan mendekripsi dan mengimpor data otomatis'],
                ['5','Laporan hasil restore (diimport / dilewati / ditimpa) akan tampil setelah selesai'],
            ] as [$num, $step])
            <div style="display:flex;align-items:flex-start;gap:12px;">
                <span style="background:#ede9fe;color:#5b21b6;border-radius:9999px;font-size:0.75rem;font-weight:700;min-width:1.5rem;height:1.5rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">{{ $num }}</span>
                <p style="font-size:0.875rem;margin:0;padding-top:1px;" class="text-gray-600 dark:text-gray-400">{{ $step }}</p>
            </div>
            @endforeach
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">🕓 Riwayat Backup</x-slot>
        <x-slot name="description">Log backup yang pernah Anda lakukan (maks. 20 terakhir)</x-slot>

        @if(empty($this->backupHistory))
            <div style="padding:40px 0;text-align:center;">
                <p style="font-size:2.5rem;margin:0 0 8px;">📭</p>
                <p class="font-medium text-gray-600 dark:text-gray-400">Belum ada riwayat backup</p>
                <p class="text-sm text-gray-400 dark:text-gray-500" style="margin-top:4px;">Klik "Download Backup Sekarang" di pojok kanan atas untuk memulai.</p>
            </div>
        @else
            <div style="display:flex;flex-direction:column;gap:8px;">
                @foreach($this->backupHistory as $i => $log)
                @php
                    $dt = \Carbon\Carbon::parse($log['created_at'])->locale('id');
                    $kb = round($log['size_bytes'] / 1024, 1);
                @endphp
                <div class="fi-section rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10 bg-white dark:bg-gray-900 shadow-sm" style="padding:12px 16px;">
                    {{-- Row: nomor + waktu | metadata --}}
                    <div style="display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:12px;">
                        {{-- Kiri --}}
                        <div style="display:flex;align-items:flex-start;gap:10px;">
                            <span style="background:#ede9fe;color:#5b21b6;border-radius:9999px;padding:1px 8px;font-size:0.7rem;font-weight:700;white-space:nowrap;line-height:1.6;">
                                #{{ $i + 1 }}
                            </span>
                            <div>
                                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $dt->isoFormat('D MMM YYYY, HH:mm') }}
                                    </span>
                                    @if($i === 0)
                                        <x-filament::badge color="success" size="xs">Terbaru</x-filament::badge>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-400 dark:text-gray-500" style="margin:2px 0 0;">{{ $dt->diffForHumans() }}</p>
                            </div>
                        </div>
                        {{-- Kanan --}}
                        <div style="display:flex;align-items:flex-start;gap:20px;flex-wrap:wrap;">
                            <div style="text-align:right;">
                                <p class="text-xs text-gray-400 dark:text-gray-500" style="margin:0;">Ukuran</p>
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200" style="margin:2px 0 0;">{{ $kb }} KB</p>
                            </div>
                            <div style="text-align:right;">
                                <p class="text-xs text-gray-400 dark:text-gray-500" style="margin:0;">Record</p>
                                <p class="text-sm font-bold text-gray-800 dark:text-gray-200" style="margin:2px 0 0;">{{ number_format($log['total_records']) }}</p>
                            </div>
                        </div>
                    </div>
                    {{-- Nama file --}}
                    <div class="border-t border-gray-100 dark:border-gray-800" style="margin-top:10px;padding-top:8px;">
                        <span class="text-xs text-gray-400 dark:text-gray-500">File:</span>
                        <code class="rounded bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300" style="font-size:0.72rem;padding:1px 6px;margin-left:4px;word-break:break-all;">{{ $log['filename'] }}</code>
                    </div>
                </div>
                @endforeach
            </div>
            <p class="text-xs text-gray-400 dark:text-gray-500" style="text-align:right;margin-top:8px;">
                Menampilkan {{ count($this->backupHistory) }} riwayat (terbaru di atas)
            </p>
        @endif
    </x-filament::section>
</x-filament-panels::page>
