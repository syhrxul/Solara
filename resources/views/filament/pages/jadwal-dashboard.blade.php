<x-filament-panels::page>
    <x-filament::section
        collapsible
        collapsed
        icon="heroicon-o-calendar"
        heading="Jadwal Sholat Harian"
        description="Klik untuk melihat detail waktu sholat hari ini berdasarkan lokasi aktif."
    >
        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>
