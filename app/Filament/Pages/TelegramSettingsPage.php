<?php

namespace App\Filament\Pages;

use App\Services\TelegramService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use UnitEnum;
use BackedEnum;

class TelegramSettingsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bell-alert';
    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';
    protected static ?int $navigationSort = 11;
    protected static ?string $title = 'Notifikasi Telegram';
    protected static ?string $navigationLabel = 'Notifikasi Telegram';
    protected string $view = 'filament.pages.telegram-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $user = auth()->user();
        $settings = $user->settings ?? [];

        $this->form->fill([
            'telegram_chat_id'       => $user->telegram_chat_id ?? '',
            'telegram_notify_schedule' => $settings['telegram_notify_schedule'] ?? true,
            'telegram_notify_tasks'    => $settings['telegram_notify_tasks'] ?? true,
            'telegram_notify_habits'   => $settings['telegram_notify_habits'] ?? false,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Koneksi Bot Telegram')
                    ->description('Hubungkan akun Anda dengan bot Telegram untuk menerima notifikasi langsung di HP.')
                    ->schema([
                        TextInput::make('telegram_chat_id')
                            ->label('Chat ID Telegram')
                            ->placeholder('Contoh: 123456789')
                            ->helperText('Kirim pesan /start ke bot @SolaraNotifBot, lalu kirim /id untuk mendapatkan Chat ID Anda.')
                            ->maxLength(50),
                    ]),

                Section::make('Jenis Notifikasi')
                    ->description('Pilih notifikasi apa saja yang ingin dikirim ke Telegram.')
                    ->schema([
                        Toggle::make('telegram_notify_schedule')
                            ->label('Jadwal Kuliah')
                            ->helperText('Kirim reminder jadwal kuliah setiap pagi.')
                            ->default(true),

                        Toggle::make('telegram_notify_tasks')
                            ->label('Tugas & Deadline')
                            ->helperText('Kirim pengingat tugas yang mendekati deadline.')
                            ->default(true),

                        Toggle::make('telegram_notify_habits')
                            ->label('Pengingat Habit')
                            ->helperText('Kirim pengingat untuk habit yang belum diselesaikan hari ini.')
                            ->default(false),
                    ])->columns(1),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('test_connection')
                ->label('Tes Koneksi')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->action('testConnection'),
        ];
    }

    public function testConnection(): void
    {
        $chatId = $this->data['telegram_chat_id'] ?? '';

        if (empty($chatId)) {
            Notification::make()
                ->title('Chat ID Kosong')
                ->body('Silakan isi Chat ID Telegram terlebih dahulu.')
                ->danger()
                ->send();
            return;
        }

        $telegram = new TelegramService();
        $botInfo = $telegram->getMe();

        if (!$botInfo) {
            Notification::make()
                ->title('Bot Token Tidak Valid')
                ->body('Bot token Telegram belum dikonfigurasi di server. Hubungi administrator.')
                ->danger()
                ->send();
            return;
        }

        $success = $telegram->sendMessage(
            $chatId,
            "✅ <b>Solara Terhubung!</b>\n\n" .
            "Notifikasi Telegram berhasil diaktifkan untuk <b>" . auth()->user()->name . "</b>.\n\n" .
            "🤖 Bot: @{$botInfo['username']}\n" .
            "📅 Waktu: " . now()->format('d M Y H:i') . " WIB"
        );

        if ($success) {
            Notification::make()
                ->title('Koneksi Berhasil! 🎉')
                ->body('Pesan tes telah dikirim ke Telegram Anda.')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Gagal Mengirim')
                ->body('Pastikan Chat ID benar dan Anda sudah mengirim /start ke bot.')
                ->danger()
                ->send();
        }
    }

    public function save(): void
    {
        $user = auth()->user();
        $formData = $this->form->getState();

        // Save chat ID to user column
        $user->update([
            'telegram_chat_id' => $formData['telegram_chat_id'] ?: null,
        ]);

        // Save notification preferences to settings
        $settings = $user->settings ?? [];
        $settings['telegram_notify_schedule'] = $formData['telegram_notify_schedule'] ?? true;
        $settings['telegram_notify_tasks'] = $formData['telegram_notify_tasks'] ?? true;
        $settings['telegram_notify_habits'] = $formData['telegram_notify_habits'] ?? false;
        $user->update(['settings' => $settings]);

        Notification::make()
            ->title('Pengaturan Telegram Disimpan!')
            ->success()
            ->send();
    }
}
