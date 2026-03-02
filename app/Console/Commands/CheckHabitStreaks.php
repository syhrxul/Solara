<?php

namespace App\Console\Commands;

use App\Models\Habit;
use App\Services\TelegramService;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;

class CheckHabitStreaks extends Command
{
    protected $signature = 'solara:check-habit-streaks';

    protected $description = 'Cek dan putuskan streak habit yang tidak dikerjakan kemarin';

    public function handle(): int
    {
        $yesterday = today()->subDay();
        $dayOfWeekYesterday = strtolower($yesterday->englishDayOfWeek);

        // Ambil semua habit aktif
        $habits = Habit::where('is_active', true)
            ->where('current_streak', '>', 0)
            ->with('user')
            ->get();

        $brokenCount = 0;

        foreach ($habits as $habit) {
            // Cek apakah habit seharusnya dikerjakan kemarin
            if (!$habit->shouldDoOnDay($dayOfWeekYesterday)) {
                continue;
            }

            // Cek apakah ada log completed untuk kemarin
            $yesterdayLog = $habit->logs()
                ->where('logged_date', $yesterday)
                ->where('completed', true)
                ->first();

            if ($yesterdayLog) {
                // Sudah dikerjakan kemarin, tidak perlu putus streak
                continue;
            }

            // Streak putus!
            $oldStreak = $habit->current_streak;
            $habit->breakStreak();
            $brokenCount++;

            $user = $habit->user;
            if (!$user) continue;

            // Kirim notifikasi database
            $title = "💔 Streak Putus: {$habit->name}";
            $body = "Streak {$oldStreak} hari Anda telah putus karena tidak diselesaikan kemarin. Anda masih bisa restore streak dalam 24 jam!";

            Notification::make()
                ->title($title)
                ->body($body)
                ->icon('heroicon-o-exclamation-triangle')
                ->iconColor('danger')
                ->sendToDatabase($user);

            // Kirim notifikasi Telegram
            if (!empty($user->telegram_chat_id)) {
                $settings = $user->settings ?? [];
                if ($settings['telegram_notify_habit'] ?? true) {
                    $telegram = new TelegramService();
                    $msg = "💔 <b>Streak Putus: {$habit->name}</b>\n\n";
                    $msg .= "Streak <b>{$oldStreak} hari</b> Anda telah putus karena tidak diselesaikan kemarin.\n\n";
                    $msg .= "⏳ <i>Anda masih bisa restore streak dalam 24 jam ke depan!</i>\n";
                    $msg .= "Ketik /habits untuk melihat detail dan restore.";
                    $telegram->sendMessage($user->telegram_chat_id, $msg);
                }
            }
        }

        $this->info("Checked streaks. {$brokenCount} streak(s) broken.");

        return self::SUCCESS;
    }
}
