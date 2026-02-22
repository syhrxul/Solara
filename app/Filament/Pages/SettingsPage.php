<?php

namespace App\Filament\Pages;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use UnitEnum;
use BackedEnum;

class SettingsPage extends Page
{

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-8-tooth';
    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';
    protected static ?int $navigationSort = 10;
    protected static ?string $title = 'Pengaturan Modul';
    protected string $view = 'filament.pages.settings-page';

    public ?array $data = [];

    public function mount(): void
    {
        $user = auth()->user();
        
        // Default settings (semua aktif jika belum diset)
        $defaultSettings = [
            'module_tasks' => true,
            'module_habits' => true,
            'module_notes' => true,
            'module_finance' => true,
            'module_goals' => true,
            'module_academic' => true,
        ];

        $userSettings = $user->settings ?? [];

        $this->form->fill(array_merge($defaultSettings, $userSettings));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Modul Aplikasi')
                    ->description('Pilih modul mana saja yang ingin Anda tampilkan di menu samping.')
                    ->schema([
                        Toggle::make('module_tasks')
                            ->label('Tasks (Tugas Harian)')
                            ->helperText('Manajemen tugas dan to-do list harian.')
                            ->default(true),
                            
                        Toggle::make('module_habits')
                            ->label('Habits (Kebiasaan)')
                            ->helperText('Pelacakan kebiasaan harian dan runtutan (streak).')
                            ->default(true),

                        Toggle::make('module_notes')
                            ->label('Catatan')
                            ->helperText('Buku catatan personal.')
                            ->default(true),

                        Toggle::make('module_academic')
                            ->label('Akademik (Jadwal & Tugas Kuliah)')
                            ->helperText('Manajemen jadwal kuliah dan tugas kampus.')
                            ->default(true),

                        Toggle::make('module_finance')
                            ->label('Keuangan')
                            ->helperText('Pencatatan pemasukan dan pengeluaran.')
                            ->default(true),

                        Toggle::make('module_goals')
                            ->label('Goals (Tujuan Kehidupan)')
                            ->helperText('Pelacakan target pencapaian jangka pendek dan panjang.')
                            ->default(true),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $user = auth()->user();
        
        $settings = $this->form->getState();
        $user->update(['settings' => $settings]);

        Notification::make()
            ->title('Pengaturan Berhasil Disimpan!')
            ->success()
            ->send();
            
        // Trigger redirect to refresh sidebar menu
        redirect(request()->header('Referer'));
    }
}
