<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class AiRiskManagerService
{
    public function evaluate(string $action, string $symbol, float $price, float $stopLossPct, float $takeProfitPct): array
    {
        $direction  = strtoupper($action) === 'BUY' ? 'покупку (лонг)' : 'продажу (шорт)';
        $riskReward = $stopLossPct > 0 ? round($takeProfitPct / $stopLossPct, 2) : 'N/A';

        $prompt = <<<PROMPT
Ты — строгий риск-менеджер криптовалютной торговли. Проанализируй сигнал и ответь JSON-объектом.

Сигнал:
- Инструмент: {$symbol}
- Направление: {$direction}
- Текущая цена: {$price} USDT
- Стоп-лосс: {$stopLossPct}%
- Тейк-профит: {$takeProfitPct}%
- Риск/Доходность: 1:{$riskReward}

Критерии отклонения:
1. Риск/Доходность менее 1:1.5
2. Стоп-лосс менее 0.5% (слишком мало для крипты)
3. Стоп-лосс более 10% (слишком высокий риск)
4. Тейк-профит более 30% (нереалистично для разового входа)

Ответь ТОЛЬКО валидным JSON без markdown-блоков:
{"decision": "approved" или "rejected", "reason": "краткое обоснование на русском языке"}
PROMPT;

        try {
            $response = OpenAI::chat()->create([
                'model'       => 'gpt-4o-mini',
                'messages'    => [['role' => 'user', 'content' => $prompt]],
                'temperature' => 0.2,
                'max_tokens'  => 200,
            ]);

            $content = $response->choices[0]->message->content ?? '{}';
            $content = preg_replace('/```json|```/', '', $content);
            $result  = json_decode(trim($content), true);

            if (!isset($result['decision'])) {
                throw new \RuntimeException('Invalid AI response structure');
            }

            Log::channel('trading')->info('AI risk assessment', [
                'symbol'   => $symbol,
                'action'   => $action,
                'decision' => $result['decision'],
                'reason'   => $result['reason'] ?? '',
            ]);

            return $result;
        } catch (\Throwable $e) {
            Log::error('AI risk manager error', ['error' => $e->getMessage()]);

            // Fail open — approve on AI error so trading isn't blocked
            return ['decision' => 'approved', 'reason' => 'ИИ недоступен — сигнал пропущен автоматически'];
        }
    }
}
