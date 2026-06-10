<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private string $token;
    private string $chatId;

    public function __construct()
    {
        $this->token  = config('telegram.bot_token', '');
        $this->chatId = config('telegram.chat_id', '');
    }

    public function send(string $message): bool
    {
        if (empty($this->token) || empty($this->chatId)) {
            Log::warning('Telegram not configured — skipping notification');
            return false;
        }

        try {
            $response = Http::post("https://api.telegram.org/bot{$this->token}/sendMessage", [
                'chat_id'    => $this->chatId,
                'text'       => $message,
                'parse_mode' => 'HTML',
            ]);

            if (!$response->successful()) {
                Log::error('Telegram send failed', ['response' => $response->json()]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Telegram exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function notifyTradeExecuted(array $trade): void
    {
        $side  = strtoupper($trade['side']) === 'BUY' ? '🟢 ЛОНГ' : '🔴 ШОРТ';
        $emoji = strtoupper($trade['side']) === 'BUY' ? '🟢' : '🔴';

        $msg = "{$emoji} ВХОД В {$side} | {$trade['symbol']}\n"
            . "💰 Цена: " . number_format((float)$trade['price'], 2) . "\n"
            . "🛡 Стоп: "  . number_format((float)$trade['stop_loss'], 2) . "\n"
            . "🎯 Тейк: "  . number_format((float)$trade['take_profit'], 2) . "\n"
            . ($trade['is_paper_trade'] ? "\n⚠️ <b>PAPER TRADE</b>" : '');

        $this->send($msg);
    }

    public function notifyRejectedByAi(string $symbol, string $action, string $reason): void
    {
        $msg = "🤖 ИИ ОТКЛОНИЛ СИГНАЛ\n"
            . "📊 {$symbol} | " . strtoupper($action) . "\n"
            . "💬 Причина: {$reason}";

        $this->send($msg);
    }

    public function notifyEmergencyStop(string $symbol, string $action): void
    {
        $msg = "🚨 АВАРИЙНАЯ ОСТАНОВКА\n"
            . "Сигнал {$symbol} / " . strtoupper($action) . " отклонён — торговля приостановлена.";

        $this->send($msg);
    }

    public function notifyError(string $symbol, string $error): void
    {
        $msg = "❌ ОШИБКА ИСПОЛНЕНИЯ\n"
            . "📊 {$symbol}\n"
            . "💬 {$error}";

        $this->send($msg);
    }
}
