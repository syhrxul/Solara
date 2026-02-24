<?php

namespace App\Notifications;

use App\Notifications\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class DailyScheduleNotification extends Notification
{
    use Queueable;

    private string $title;
    private string $body;

    public function __construct(string $title, string $body)
    {
        $this->title = $title;
        $this->body = $body;
    }

    public function via($notifiable): array
    {
        $channels = [WebPushChannel::class];

        // Add Telegram if user has chat_id and enabled schedule notifications
        if (!empty($notifiable->telegram_chat_id)) {
            $settings = $notifiable->settings ?? [];
            if ($settings['telegram_notify_schedule'] ?? true) {
                $channels[] = TelegramChannel::class;
            }
        }

        return $channels;
    }

    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title($this->title)
            ->body($this->body)
            ->icon('/favicon.ico')
            ->data(['url' => url('/app/class-schedules')]);
    }

    public function toTelegram($notifiable): string
    {
        return "📅 <b>{$this->title}</b>\n\n{$this->body}";
    }
}
