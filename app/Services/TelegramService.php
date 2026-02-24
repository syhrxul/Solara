<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected string $botToken;
    protected string $apiUrl;

    public function __construct()
    {
        $this->botToken = (string) config('services.telegram.bot_token', '');
        $this->apiUrl = "https://api.telegram.org/bot{$this->botToken}";
    }

    /**
     * Send a text message to a Telegram chat.
     */
    public function sendMessage(string $chatId, string $message, ?string $parseMode = 'HTML', ?array $replyMarkup = null): bool
    {
        if (empty($this->botToken) || empty($chatId)) {
            Log::warning('Telegram: Bot token or chat ID is empty.');
            return false;
        }

        try {
            $payload = [
                'chat_id'    => $chatId,
                'text'       => $message,
                'parse_mode' => $parseMode,
            ];

            if ($replyMarkup) {
                $payload['reply_markup'] = json_encode($replyMarkup);
            }

            $response = Http::post("{$this->apiUrl}/sendMessage", $payload);

            if ($response->successful() && $response->json('ok')) {
                return true;
            }

            Log::error('Telegram API Error: ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error('Telegram Service Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Set a webhook for Telegram bot.
     */
    public function setWebhook(string $url): bool
    {
        if (empty($this->botToken)) return false;

        try {
            $response = Http::post("{$this->apiUrl}/setWebhook", ['url' => $url]);
            return $response->successful() && $response->json('ok');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get bot info to verify token is valid.
     */
    public function getMe(): ?array
    {
        if (empty($this->botToken)) {
            return null;
        }

        try {
            $response = Http::get("{$this->apiUrl}/getMe");

            if ($response->successful() && $response->json('ok')) {
                return $response->json('result');
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Validate a chat ID by sending a test message.
     */
    public function validateChatId(string $chatId): bool
    {
        return $this->sendMessage($chatId, '✅ <b>Solara Terhubung!</b>\n\nNotifikasi Telegram berhasil diaktifkan.');
    }
}
