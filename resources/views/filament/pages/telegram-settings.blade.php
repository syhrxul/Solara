<x-filament::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex justify-end gap-3">
            <x-filament::button type="submit">
                Simpan Pengaturan
            </x-filament::button>
        </div>
    </form>

    {{-- Panduan --}}
    <x-filament::section class="mt-6" collapsible collapsed>
        <x-slot name="heading">📖 Cara Mendapatkan Chat ID</x-slot>

        <div class="prose dark:prose-invert max-w-none text-sm">
            <ol class="space-y-2">
                <li>Buka Telegram di HP atau laptop.</li>
                <li>Cari bot <strong>@SolaraNotifBot</strong> (atau bot yang sudah dikonfigurasi admin).</li>
                <li>Kirim pesan <code>/start</code> untuk memulai percakapan.</li>
                <li>Kirim pesan <code>/id</code> — bot akan membalas dengan Chat ID Anda.</li>
                <li>Salin Chat ID tersebut dan tempel di kolom di atas.</li>
                <li>Klik <strong>"Tes Koneksi"</strong> untuk memastikan bot bisa mengirim pesan ke Anda.</li>
            </ol>

            <div class="mt-4 p-3 rounded-lg bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800">
                <p class="text-amber-700 dark:text-amber-400 text-xs m-0">
                    <strong>💡 Tips:</strong> Jika Anda tidak tahu bot Telegram yang digunakan, hubungi administrator sistem untuk mendapatkan username bot.
                </p>
            </div>
        </div>
    </x-filament::section>
</x-filament::page>
