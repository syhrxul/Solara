<?php

namespace App\Console\Commands;

use App\Models\Habit;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use App\Services\TelegramService;

class SendHabitReminders extends Command
{
    protected $signature = 'solara:notify-habits';

    protected $description = 'Kirim pengingat habit ke user sesuai jam reminder_time';

    public function handle(): int
    {
        $nowTime = now()->format('H:i');
        $dayOfWeek = strtolower(now()->englishDayOfWeek);

        $habits = Habit::where('is_active', true)
            ->whereNotNull('reminder_time')
            ->where('reminder_time', 'like', $nowTime . '%')
            ->with('user')
            ->get();

        if ($habits->isEmpty()) {
            return self::SUCCESS;
        }

        foreach ($habits as $habit) {
            $user = $habit->user;
            if (!$user) continue;

            $shouldDoToday = false;
            if ($habit->frequency === 'daily') {
                $shouldDoToday = true;
            } elseif ($habit->frequency === 'weekly') {
                $days = is_string($habit->frequency_days) ? json_decode($habit->frequency_days, true) : $habit->frequency_days;
                if (is_array($days) && in_array($dayOfWeek, $days)) {
                    $shouldDoToday = true;
                }
            }

            if (!$shouldDoToday || $habit->isCompletedToday()) {
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

            // Telegram Notification
            if (!empty($user->telegram_chat_id)) {
                $settings = $user->settings ?? [];
                if ($settings['telegram_notify_habit'] ?? true) {
                    $telegram = new TelegramService();
                    $msg = "⏰ <b>Waktunya: {$habit->name}</b>\n\nJangan lupa selesaikan habit Anda hari ini! Ayo pertahankan rentetan capaianmu 🔥";
                    $telegram->sendMessage($user->telegram_chat_id, $msg);
                }
            }
        }

        return self::SUCCESS;
    }
}
