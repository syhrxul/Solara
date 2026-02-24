<?php

namespace App\Notifications\Channels;

use App\Services\TelegramService;
use Illuminate\Notifications\Notification;

class TelegramChannel
{
    public function send($notifiable, Notification $notification)
    {
        $chatId = $notifiable->telegram_chat_id;

        if (empty($chatId)) {
            return;
        }

        // Check if user has enabled this notification type
        $settings = $notifiable->settings ?? [];

        // Get the telegram message from the notification
        if (method_exists($notification, 'toTelegram')) {
            $message = $notification->toTelegram($notifiable);

            if ($message) {
                $telegram = new TelegramService();
                $telegram->sendMessage($chatId, $message);
            }
        }
    }
}
