<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Stats Overview --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            @php
                $stats = [
                    ['label' => 'Tasks', 'icon' => '✅', 'count' => $this->backupStats['tasks']],
                    ['label' => 'Habits', 'icon' => '🔄', 'count' => $this->backupStats['habits']],
                    ['label' => 'Habit Logs', 'icon' => '📒', 'count' => $this->backupStats['habit_logs']],
                    ['label' => 'Catatan', 'icon' => '🗒️', 'count' => $this->backupStats['notes']],
                    ['label' => 'Jadwal Kuliah', 'icon' => '🎓', 'count' => $this->backupStats['schedules']],
                    ['label' => 'Tugas Kuliah', 'icon' => '📚', 'count' => $this->backupStats['assignments']],
                    ['label' => 'Transaksi', 'icon' => '💰', 'count' => $this->backupStats['finances']],
                    ['label' => 'Goals', 'icon' => '🎯', 'count' => $this->backupStats['goals']],
                ];
                $total = array_sum(array_column($stats, 'count'));
            @endphp

            @foreach($stats as $stat)
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-2xl">{{ $stat['icon'] }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($stat['count']) }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
            </div>
            @endforeach
        </div>

        {{-- Backup Info Card --}}
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-6 dark:border-blue-800 dark:bg-blue-950">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 text-4xl">🗂️</div>
                <div>
                    <h3 class="text-lg font-bold text-blue-800 dark:text-blue-200">Backup Data Akun Anda</h3>
                    <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                        Klik tombol <strong>"Download Backup Sekarang"</strong> di pojok kanan atas untuk mengunduh seluruh data Anda dalam format <strong>.slr</strong> yang telah <strong>dienkripsi (AES-256)</strong>. 
                        File hanya bisa dibaca oleh sistem Solara dengan akun Anda sendiri &mdash; aman dari orang lain.
                    </p>
                    <p class="mt-3 text-xs text-blue-600 dark:text-blue-400">
                        📦 Total <strong>{{ number_format($total) }} record</strong> akan dibackup •
                        🔐 Terenkripsi AES-256-CBC, tidak bisa dibaca manual •
                        📅 Waktu backup: {{ now()->locale('id')->isoFormat('D MMMM YYYY, HH:mm') }} WIB
                    </p>
                </div>
            </div>
        </div>

        {{-- Info sections --}}
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="rounded-xl border border-green-200 bg-green-50 p-5 dark:border-green-800 dark:bg-green-950">
                <h4 class="font-semibold text-green-800 dark:text-green-200">✅ Apa yang terbackup?</h4>
                <ul class="mt-2 space-y-1 text-sm text-green-700 dark:text-green-300 list-disc list-inside">
                    <li>Semua task &amp; to-do list personal</li>
                    <li>Seluruh habit beserta log penyelesaian</li>
                    <li>Catatan-catatan (Notes)</li>
                    <li>Jadwal kuliah &amp; tugas kampus</li>
                    <li>Transaksi keuangan (pemasukan &amp; pengeluaran)</li>
                    <li>Goal &amp; milestone pencapaian</li>
                </ul>
            </div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-800 dark:bg-amber-950">
                <h4 class="font-semibold text-amber-800 dark:text-amber-200">💡 Tentang Format .slr</h4>
                <ul class="mt-2 space-y-1 text-sm text-amber-700 dark:text-amber-300 list-disc list-inside">
                    <li>File <code>.slr</code> adalah format backup eksklusif Solara</li>
                    <li>Isi data dienkripsi dengan <strong>AES-256-CBC</strong></li>
                    <li>Tidak bisa dibuka dengan teks editor biasa</li>
                    <li>Kunci enkripsi unik per‑akun &mdash; hanya bisa direstore di akun Anda</li>
                    <li>Disarankan backup minimal 1&times; seminggu</li>
                </ul>
            </div>
        </div>
    </div>
</x-filament-panels::page>
