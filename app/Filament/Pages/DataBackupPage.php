<?php

namespace App\Filament\Pages;

use App\Models\ClassAssignment;
use App\Models\ClassSchedule;
use App\Models\FinanceTransaction;
use App\Models\Goal;
use App\Models\GoalMilestone;
use App\Models\Habit;
use App\Models\HabitLog;
use App\Models\Note;
use App\Models\Task;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
    public ?string $lastBackupAt = null;
    public array $backupHistory = [];

    public function mount(): void
    {
        $user = auth()->user();
        $settings = $user->settings ?? [];

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

        $history = $settings['backup_history'] ?? [];
        $this->backupHistory = array_reverse(array_slice($history, -20));
        $this->lastBackupAt = !empty($history) ? end($history)['created_at'] : null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('restore_backup')
                ->label('Restore Backup')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->modalHeading('Restore Data dari File .slr')
                ->modalDescription('Upload file backup Solara (.slr) Anda, lalu pilih bagaimana menangani data yang sudah ada.')
                ->modalSubmitActionLabel('Mulai Restore')
                ->modalWidth('lg')
                ->form([
                    FileUpload::make('backup_file')
                        ->label('File Backup (.slr)')
                        ->disk('local')
                        ->directory('tmp-restore')
                        ->required()
                        ->helperText('Hanya file .slr yang dihasilkan oleh Solara yang valid.'),

                    Radio::make('conflict_mode')
                        ->label('Jika data sudah ada di database')
                        ->options([
                            'skip'      => '⏭️ Lewati (Skip) — data lama dipertahankan',
                            'overwrite' => '🔄 Timpa (Overwrite) — data lama diganti dengan data backup',
                        ])
                        ->default('skip')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->performRestore($data);
                }),

            Action::make('download_backup')
                ->label('Download Backup')
                ->icon('heroicon-o-cloud-arrow-down')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Backup Data')
                ->modalDescription('Seluruh data akun Anda akan dikemas dalam satu file .slr yang terenkripsi. Lanjutkan?')
                ->modalSubmitActionLabel('Ya, Download Sekarang!')
                ->action('downloadBackup'),
        ];
    }

    public function performRestore(array $data): void
    {
        $user = auth()->user();
        $mode = $data['conflict_mode']; // 'skip' | 'overwrite'

        // FileUpload may return array or string depending on Filament version
        $filePath = is_array($data['backup_file']) ? array_values($data['backup_file'])[0] : $data['backup_file'];

        // Read binary content — try the raw path first, then with tmp-restore prefix
        $raw = Storage::disk('local')->get($filePath)
            ?? Storage::disk('local')->get('tmp-restore/' . basename($filePath));

        // Also try system temp dir as a last resort (Livewire temp uploads)
        if (!$raw && file_exists(sys_get_temp_dir() . '/' . basename($filePath))) {
            $raw = file_get_contents(sys_get_temp_dir() . '/' . basename($filePath));
        }

        if (!$raw) {
            Notification::make()
                ->title('File Tidak Bisa Dibaca')
                ->body('File tidak dapat dibaca dari server. Coba upload ulang.')
                ->danger()
                ->send();
            return;
        }

        // Validate SLR magic header: "SLR\x01"
        if (substr($raw, 0, 4) !== "SLR\x01") {
            // Show first 10 bytes hex for debugging
            $hexHeader = bin2hex(substr($raw, 0, 10));
            Notification::make()
                ->title('File Tidak Valid')
                ->body("File yang diunggah bukan file backup Solara (.slr) yang valid. Header: {$hexHeader}")
                ->danger()
                ->send();

            Storage::disk('local')->delete($filePath);
            return;
        }

        // Decrypt — try app-key-only first (new format), fallback to old key with user_id
        $encKeyNew = hash('sha256', config('app.key'), true);
        $encKeyOld = hash('sha256', config('app.key') . '::' . $user->id, true);
        $iv         = substr($raw, 4, 16);
        $ciphertext = substr($raw, 20);

        $json = openssl_decrypt($ciphertext, 'AES-256-CBC', $encKeyNew, OPENSSL_RAW_DATA, $iv);
        if ($json === false) {
            // Fallback: try old key (backup made before this fix)
            $json = openssl_decrypt($ciphertext, 'AES-256-CBC', $encKeyOld, OPENSSL_RAW_DATA, $iv);
        }

        if ($json === false) {
            Notification::make()
                ->title('Dekripsi Gagal')
                ->body('File tidak bisa didekripsi. Pastikan file ini adalah file .slr yang dihasilkan oleh Solara.')
                ->danger()
                ->send();

            Storage::disk('local')->delete($filePath);
            return;
        }

        $backup = json_decode($json, true);
        if (!isset($backup['data'])) {
            Notification::make()
                ->title('Format Tidak Dikenali')
                ->body('Struktur data backup tidak valid.')
                ->danger()
                ->send();

            Storage::disk('local')->delete($filePath);
            return;
        }

        $imported = 0;
        $skipped  = 0;
        $overwrote = 0;

        DB::transaction(function () use ($user, $backup, $mode, &$imported, &$skipped, &$overwrote) {
            $d = $backup['data'];

            // Helper closure
            $restore = function (string $model, array $records, array $matchKeys, array $skipFields = []) use ($user, $mode, &$imported, &$skipped, &$overwrote) {
                foreach ($records as $record) {
                    // Always bind to current user
                    $record['user_id'] = $user->id;

                    // Build match conditions
                    $match = ['user_id' => $user->id];
                    foreach ($matchKeys as $key) {
                        $match[$key] = $record[$key] ?? null;
                    }

                    // Remove auto-managed fields before upsert
                    $fill = array_diff_key($record, array_flip(array_merge(['id', 'created_at', 'updated_at'], $skipFields)));

                    $existing = $model::where($match)->first();

                    if ($existing) {
                        if ($mode === 'overwrite') {
                            $existing->update($fill);
                            $overwrote++;
                        } else {
                            $skipped++;
                        }
                    } else {
                        $model::create(array_merge($fill, ['created_at' => $record['created_at'] ?? now(), 'updated_at' => $record['updated_at'] ?? now()]));
                        $imported++;
                    }
                }
            };

            $restore(Task::class, $d['tasks'] ?? [], ['title']);
                                
            $restore(Habit::class, $d['habits'] ?? [], ['name'], ['logs']);

            foreach ($d['habit_logs'] ?? [] as $log) {
                $log['user_id'] = $user->id;

                $habitName = collect($d['habits'] ?? [])->firstWhere('id', $log['habit_id'])['name'] ?? null;
                $habit = $habitName ? Habit::where('user_id', $user->id)->where('name', $habitName)->first() : null;

                if (!$habit) { $skipped++; continue; }

                $loggedDate = \Carbon\Carbon::parse($log['logged_date'])->toDateString();

                $fill = [
                    'user_id'     => $user->id,
                    'habit_id'    => $habit->id,
                    'logged_date' => $loggedDate,
                    'completed'   => $log['completed'] ?? false,
                    'count'       => $log['count'] ?? 1,
                ];

                $existing = HabitLog::where('user_id', $user->id)
                    ->where('habit_id', $habit->id)
                    ->whereDate('logged_date', $loggedDate)
                    ->first();

                if ($existing) {
                    if ($mode === 'overwrite') {
                        $existing->update($fill);
                        $overwrote++;
                    } else {
                        $skipped++;
                    }
                } else {
                    HabitLog::create($fill);
                    $imported++;
                }
            }

            // Notes: match by title
            $restore(Note::class, $d['notes'] ?? [], ['title']);


            $restore(ClassSchedule::class, $d['class_schedules'] ?? [], ['subject', 'day']);

            // Class Assignments: match by title
            $restore(ClassAssignment::class, $d['class_assignments'] ?? [], ['title']);

            // Finance Transactions: match by amount + date + type
            $restore(FinanceTransaction::class, $d['finance_transactions'] ?? [], ['amount', 'date', 'type']);

            // Goals: match by title
            $restore(Goal::class, $d['goals'] ?? [], ['title'], ['milestones']);
        });

        // Cleanup temp file
        Storage::disk('local')->delete($filePath);

        // Refresh stats
        $this->mount();

        $modeLabel = $mode === 'overwrite' ? 'Timpa aktif' : 'Skip aktif';
        Notification::make()
            ->title('Restore Selesai! 🎉')
            ->body("✅ Diimport: {$imported} | ⏭️ Dilewati: {$skipped} | 🔄 Ditimpa: {$overwrote}\n({$modeLabel})")
            ->success()
            ->persistent()
            ->send();
    }

    // ============================
    // DOWNLOAD / BACKUP
    // ============================
    public function downloadBackup(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $user = auth()->user();

        $backup = [
            'meta' => [
                'app'         => 'Solara',
                'version'     => '1.0',
                'exported_at' => now()->toIso8601String(),
                'user'        => ['name' => $user->name, 'email' => $user->email],
            ],
            'data' => [
                'tasks'                => Task::where('user_id', $user->id)->get()->map(fn ($t) => $t->toArray())->toArray(),
                'habits'               => Habit::where('user_id', $user->id)->with('logs')->get()->map(fn ($h) => $h->toArray())->toArray(),
                'habit_logs'           => HabitLog::where('user_id', $user->id)->get()->map(fn ($l) => $l->toArray())->toArray(),
                'notes'                => Note::where('user_id', $user->id)->get()->map(fn ($n) => $n->toArray())->toArray(),
                'class_schedules'      => ClassSchedule::where('user_id', $user->id)->get()->map(fn ($s) => $s->toArray())->toArray(),
                'class_assignments'    => ClassAssignment::where('user_id', $user->id)->get()->map(fn ($a) => $a->toArray())->toArray(),
                'finance_transactions' => FinanceTransaction::where('user_id', $user->id)->get()->map(fn ($f) => $f->toArray())->toArray(),
                'goals'                => Goal::where('user_id', $user->id)->with('milestones')->get()->map(fn ($g) => $g->toArray())->toArray(),
            ],
        ];

        // Encrypt with APP_KEY only — portable across accounts on the same server
        $encKey    = hash('sha256', config('app.key'), true);
        $iv        = random_bytes(16);
        $json      = json_encode($backup, JSON_UNESCAPED_UNICODE);
        $encrypted = openssl_encrypt($json, 'AES-256-CBC', $encKey, OPENSSL_RAW_DATA, $iv);
        $slrData   = "SLR\x01" . $iv . $encrypted;

        // Log backup
        $totalRecords = array_sum($this->backupStats);
        $settings     = $user->settings ?? [];
        $history      = $settings['backup_history'] ?? [];
        $filename     = 'solara-backup-' . now()->format('Y-m-d-His') . '.slr';
        $history[]    = ['created_at' => now()->toIso8601String(), 'filename' => $filename, 'size_bytes' => strlen($slrData), 'total_records' => $totalRecords];
        $settings['backup_history'] = array_slice($history, -50);
        $user->update(['settings' => $settings]);

        Notification::make()->title('Backup Berhasil! 🎉')->body('File .slr terenkripsi berhasil diunduh.')->success()->send();

        return response()->streamDownload(function () use ($slrData) {
            echo $slrData;
        }, $filename, ['Content-Type' => 'application/octet-stream']);
    }
}
