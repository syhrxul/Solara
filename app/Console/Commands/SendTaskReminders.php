<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Task;
use App\Models\ClassAssignment;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use App\Services\TelegramService;
use Carbon\Carbon;

class SendTaskReminders extends Command
{
    protected $signature = 'solara:notify-tasks';

    protected $description = 'Kirim pengingat tugas (kuliah dan biasa) yang akan segera melewati batas waktu (due hari ini atau besok)';

    public function handle(): int
    {
        $users = User::all();
        $today = Carbon::today('Asia/Jakarta');
        $tomorrow = Carbon::tomorrow('Asia/Jakarta');

        foreach ($users as $user) {
            $hasNotification = false;
            $msg = "🔔 <b>PENGINGAT DEADLINE TUGAS</b>\n\nJangan lupa, Anda memiliki tugas/task yang mendekati deadline:\n\n";
            $dbMsg = [];

            // 1. Cek Tugas Kuliah (ClassAssignment)
            $assignments = ClassAssignment::where('user_id', $user->id)
                ->where('status', '!=', 'selesai')
                ->whereNotNull('deadline')
                ->get();

            $pendingAssign = [];
            foreach ($assignments as $assign) {
                // If deadline is today or tomorrow (using date logic)
                if ($assign->deadline && ($assign->deadline->isToday() || $assign->deadline->isTomorrow() || $assign->deadline->isPast())) {
                    $batasWaktu = $assign->deadline->isPast() ? 'LEWAT BATAS' : ($assign->deadline->isToday() ? 'HARI INI' : 'BESOK');
                    $pendingAssign[] = "• <b>{$assign->title}</b> (Deadline: {$batasWaktu})";
                }
            }

            if (!empty($pendingAssign)) {
                $hasNotification = true;
                $msg .= "<b>📚 Tugas Kuliah:</b>\n" . implode("\n", $pendingAssign) . "\n\n";
                $dbMsg[] = "Tugas Kuliah: " . count($pendingAssign);
            }

            // 2. Cek To-Do List (Tasks)
            $tasks = Task::where('user_id', $user->id)
                ->pending()
                ->whereNotNull('due_date')
                ->get();

            $pendingTasks = [];
            foreach ($tasks as $task) {
                if ($task->due_date && ($task->due_date->isToday() || $task->due_date->isTomorrow() || $task->due_date->isPast())) {
                    $batasWaktu = $task->isOverdue() ? 'LEWAT BATAS' : ($task->due_date->isToday() ? 'HARI INI' : 'BESOK');
                    $pendingTasks[] = "• <b>{$task->title}</b> (Due: {$batasWaktu})";
                }
            }

            if (!empty($pendingTasks)) {
                $hasNotification = true;
                $msg .= "<b>✅ To-Do List (Personal):</b>\n" . implode("\n", $pendingTasks) . "\n\n";
                $dbMsg[] = "To-Do List: " . count($pendingTasks);
            }

            // Send Notifications if anything is due
            if ($hasNotification) {
                $msg .= "<i>Segera selesaikan sebelum menumpuk! 💪</i>";

                // Database
                Notification::make()
                    ->title('Pengingat Deadline Tugas 🚨')
                    ->body('Ada tugas yang mendekati atau melewati tenggat waktu: ' . implode(', ', $dbMsg))
                    ->icon('heroicon-o-exclamation-circle')
                    ->iconColor('danger')
                    ->sendToDatabase($user);

                // Telegram
                if (!empty($user->telegram_chat_id)) {
                    $settings = $user->settings ?? [];
                    if ($settings['telegram_notify_task'] ?? true) { // Asumsi ada config telegram_notify_task
                        $telegram = new TelegramService();
                        $telegram->sendMessage($user->telegram_chat_id, $msg, 'HTML');
                    }
                }
            }
        }

        $this->info('Pengingat tugas berhasil dikirim!');
        return self::SUCCESS;
    }
}
