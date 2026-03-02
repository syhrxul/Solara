<?php

namespace App\Console\Commands;

use App\Models\Habit;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Log;

class SendHabitReminders extends Command
{
    protected $signature = 'solara:notify-habits';

    protected $description = 'Kirim pengingat habit ke user sesuai jam reminder_time';

    public function handle(): int
    {
        $now = now();
        $nowTime = $now->format('H:i');
        $dayOfWeek = strtolower($now->englishDayOfWeek);

        Log::info("SendHabitReminders: Checking habits for time {$nowTime}");

        $habits = Habit::where('is_active', true)
            ->whereNotNull('reminder_time')
            ->whereRaw("DATE_FORMAT(reminder_time, '%H:%i') = ?", [$nowTime])
            ->with('user')
            ->get();

        Log::info("SendHabitReminders: Found {$habits->count()} habits matching time {$nowTime}");

        if ($habits->isEmpty()) {
            return self::SUCCESS;
        }

        foreach ($habits as $habit) {
            $user = $habit->user;
            if (!$user) continue;

            // Check frequency menggunakan method model
            if (!$habit->shouldDoOnDay($dayOfWeek)) {
                continue;
            }

            if ($habit->isCompletedToday()) {
                continue;
            }

            // Database Notification
            $title = "⏰ Waktunya Habits: {$habit->name}";
            $body = "Jangan lupa selesaikan habit Anda hari ini untuk mempertahankan current streak!";
            
            Notification::make()
                ->title($title)
                ->body($body)
                ->icon('heroicon-o-fire')
                ->iconColor('warning')
                ->sendToDatabase($user);

            Log::info("SendHabitReminders: DB notification sent for habit '{$habit->name}' to user '{$user->name}'");

            // Telegram Notification
            if (!empty($user->telegram_chat_id)) {
                $settings = $user->settings ?? [];
                if ($settings['telegram_notify_habit'] ?? true) {
                    try {
                        $telegram = new TelegramService();
                        
                        $streakInfo = $habit->current_streak > 0
                            ? "Streak saat ini: <b>{$habit->current_streak} 🔥</b>"
                            : "Mulai bangun streak baru! 💪";

                        $msg = "⏰ <b>Waktunya: {$habit->name}</b>\n\n";
                        $msg .= "Jangan lupa selesaikan habit Anda hari ini!\n";
                        $msg .= "{$streakInfo}\n\n";
                        $msg .= "Ayo pertahankan konsistensimu 🔥";

                        $inlineBtn = [
                            'inline_keyboard' => [
                                [
                                    ['text' => '✅ Tandai Selesai', 'callback_data' => 'chk_hab_' . $habit->id]
                                ]
                            ]
                        ];

                        $result = $telegram->sendMessage($user->telegram_chat_id, $msg, 'HTML', $inlineBtn);
                        Log::info("SendHabitReminders: Telegram notification for habit '{$habit->name}' " . ($result ? 'sent OK' : 'FAILED'));
                    } catch (\Exception $e) {
                        Log::error("SendHabitReminders: Telegram send error for habit '{$habit->name}': " . $e->getMessage());
                    }
                }
            }
        }

        return self::SUCCESS;
    }
}
