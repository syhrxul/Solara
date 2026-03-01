<?php

namespace App\Filament\Pages;

use App\Models\ClassAssignment;
use App\Models\ClassSchedule;
use App\Models\FinanceTransaction;
use App\Models\Goal;
use App\Models\Habit;
use App\Models\HabitLog;
use App\Models\Note;
use App\Models\Task;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use UnitEnum;

class DataBackupPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';
    protected static ?int $navigationSort = 12;
    protected static ?string $title = 'Backup Data';
    protected static ?string $navigationLabel = 'Backup Data';
    protected string $view = 'filament.pages.data-backup';

    public array $backupStats = [];

    public function mount(): void
    {
        $user = auth()->user();

        $this->backupStats = [
            'tasks'        => Task::where('user_id', $user->id)->count(),
            'habits'       => Habit::where('user_id', $user->id)->count(),
            'habit_logs'   => HabitLog::where('user_id', $user->id)->count(),
            'notes'        => Note::where('user_id', $user->id)->count(),
            'schedules'    => ClassSchedule::where('user_id', $user->id)->count(),
            'assignments'  => ClassAssignment::where('user_id', $user->id)->count(),
            'finances'     => FinanceTransaction::where('user_id', $user->id)->count(),
            'goals'        => Goal::where('user_id', $user->id)->count(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_backup')
                ->label('Download Backup Sekarang')
                ->icon('heroicon-o-cloud-arrow-down')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Backup Data')
                ->modalDescription('Seluruh data akun Anda akan dikemas dalam satu file slr. Proses ini aman dan hanya mencakup data milik Anda. Lanjutkan?')
                ->modalSubmitActionLabel('Ya, Download Sekarang!')
                ->action('downloadBackup'),
        ];
    }

    public function downloadBackup(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $user = auth()->user();

        $backup = [
            'meta' => [
                'app'         => 'Solara',
                'version'     => '1.0',
                'exported_at' => now()->toIso8601String(),
                'user'        => [
                    'name'  => $user->name,
                    'email' => $user->email,
                ],
            ],
            'data' => [
                'tasks' => Task::where('user_id', $user->id)
                    ->get()->map(fn ($t) => $t->toArray())->toArray(),

                'habits' => Habit::where('user_id', $user->id)
                    ->with('logs')->get()->map(fn ($h) => $h->toArray())->toArray(),

                'habit_logs' => HabitLog::where('user_id', $user->id)
                    ->get()->map(fn ($l) => $l->toArray())->toArray(),

                'notes' => Note::where('user_id', $user->id)
                    ->get()->map(fn ($n) => $n->toArray())->toArray(),

                'class_schedules' => ClassSchedule::where('user_id', $user->id)
                    ->get()->map(fn ($s) => $s->toArray())->toArray(),

                'class_assignments' => ClassAssignment::where('user_id', $user->id)
                    ->get()->map(fn ($a) => $a->toArray())->toArray(),

                'finance_transactions' => FinanceTransaction::where('user_id', $user->id)
                    ->get()->map(fn ($f) => $f->toArray())->toArray(),

                'goals' => Goal::where('user_id', $user->id)
                    ->with('milestones')->get()->map(fn ($g) => $g->toArray())->toArray(),
            ],
        ];

        // Derive encryption key: SHA-256(APP_KEY + user_id) => 32 bytes for AES-256
        $encKey = hash('sha256', config('app.key') . '::' . $user->id, true);

        // Generate random IV
        $iv = random_bytes(16);

        // Encrypt JSON payload
        $json      = json_encode($backup, JSON_UNESCAPED_UNICODE);
        $encrypted = openssl_encrypt($json, 'AES-256-CBC', $encKey, OPENSSL_RAW_DATA, $iv);

        // Format: [4-byte magic "SLR\x01"] + [16-byte IV] + [ciphertext]
        $slrData = "SLR\x01" . $iv . $encrypted;

        $filename = 'solara-backup-' . now()->format('Y-m-d-His') . '.slr';

        Notification::make()
            ->title('Backup Berhasil! 🎉')
            ->body('File backup terenkripsi (.slr) Anda sudah siap diunduh.')
            ->success()
            ->send();

        return response()->streamDownload(function () use ($slrData) {
            echo $slrData;
        }, $filename, [
            'Content-Type' => 'application/octet-stream',
        ]);
    }
}
