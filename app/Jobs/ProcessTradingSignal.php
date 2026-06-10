<?php

namespace App\Jobs;

use App\Models\Setting;
use App\Models\Signal;
use App\Models\Trade;
use App\Services\AiRiskManagerService;
use App\Services\BybitService;
use App\Services\TelegramService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessTradingSignal implements ShouldQueue
{
    use Queueable;

    public int $tries    = 3;
    public int $timeout  = 60;
    public int $backoff  = 10;

    public function __construct(public readonly int $signalId)
    {
        $this->onQueue('trading');
    }

    public function handle(
        BybitService $bybit,
        TelegramService $telegram,
        AiRiskManagerService $aiManager
    ): void {
        $signal = Signal::findOrFail($this->signalId);

        if ($signal->status !== 'pending') {
            return;
        }

        $signal->update(['status' => 'processing']);

        try {
            // Emergency stop check
            if (Setting::isEmergencyStop()) {
                $signal->update(['status' => 'rejected_emergency']);
                $telegram->notifyEmergencyStop($signal->symbol, $signal->action);
                return;
            }

            // AI filter
            if (Setting::isAiFilterEnabled()) {
                $assessment = $aiManager->evaluate(
                    $signal->action,
                    $signal->symbol,
                    (float) $signal->price,
                    (float) $signal->stop_loss_pct,
                    (float) $signal->take_profit_pct
                );

                $signal->update([
                    'ai_decision' => $assessment['decision'],
                    'ai_reason'   => $assessment['reason'] ?? null,
                ]);

                if ($assessment['decision'] !== 'approved') {
                    $signal->update(['status' => 'rejected_by_ai']);
                    $telegram->notifyRejectedByAi(
                        $signal->symbol,
                        $signal->action,
                        $assessment['reason'] ?? 'Без объяснений'
                    );
                    return;
                }
            }

            // Calculate stop-loss and take-profit prices
            $price      = (float) $signal->price;
            $slPct      = (float) $signal->stop_loss_pct;
            $tpPct      = (float) $signal->take_profit_pct;
            $isBuy      = strtolower($signal->action) === 'buy';
            $stopLoss   = $isBuy
                ? round($price * (1 - $slPct / 100), 8)
                : round($price * (1 + $slPct / 100), 8);
            $takeProfit = $isBuy
                ? round($price * (1 + $tpPct / 100), 8)
                : round($price * (1 - $tpPct / 100), 8);

            $isPaperTrade = (bool) Setting::get('paper_trade', true);

            if ($isPaperTrade) {
                $this->executePaperTrade($signal, $stopLoss, $takeProfit, $telegram);
                return;
            }

            // Real Bybit order
            $result = $bybit->placeOrder([
                'symbol'      => $signal->symbol,
                'side'        => $isBuy ? 'Buy' : 'Sell',
                'qty'         => (string) $signal->volume,
                'stopLoss'    => (string) $stopLoss,
                'takeProfit'  => (string) $takeProfit,
                'slTriggerBy' => 'MarkPrice',
                'tpTriggerBy' => 'MarkPrice',
            ]);

            Trade::create([
                'signal_id'          => $signal->id,
                'bybit_order_id'     => $result['orderId'] ?? null,
                'bybit_order_link_id'=> $result['orderLinkId'] ?? null,
                'symbol'             => $signal->symbol,
                'side'               => $isBuy ? 'Buy' : 'Sell',
                'qty'                => $signal->volume,
                'price'              => $price,
                'stop_loss'          => $stopLoss,
                'take_profit'        => $takeProfit,
                'status'             => $result['orderStatus'] ?? 'Created',
                'bybit_response'     => $result,
                'is_paper_trade'     => false,
            ]);

            $signal->update(['status' => 'executed']);

            $telegram->notifyTradeExecuted([
                'symbol'       => $signal->symbol,
                'side'         => $isBuy ? 'BUY' : 'SELL',
                'price'        => $price,
                'stop_loss'    => $stopLoss,
                'take_profit'  => $takeProfit,
                'is_paper_trade' => false,
            ]);
        } catch (\Throwable $e) {
            Log::error('Signal processing failed', [
                'signal_id' => $this->signalId,
                'error'     => $e->getMessage(),
            ]);

            $signal->markFailed($e->getMessage());
            $telegram->notifyError($signal->symbol, $e->getMessage());

            throw $e;
        }
    }

    private function executePaperTrade(Signal $signal, float $stopLoss, float $takeProfit, TelegramService $telegram): void
    {
        $isBuy = strtolower($signal->action) === 'buy';

        Trade::create([
            'signal_id'   => $signal->id,
            'symbol'      => $signal->symbol,
            'side'        => $isBuy ? 'Buy' : 'Sell',
            'qty'         => $signal->volume,
            'price'       => (float) $signal->price,
            'stop_loss'   => $stopLoss,
            'take_profit' => $takeProfit,
            'status'      => 'PaperFilled',
            'is_paper_trade' => true,
        ]);

        $signal->update(['status' => 'executed']);

        $telegram->notifyTradeExecuted([
            'symbol'       => $signal->symbol,
            'side'         => $isBuy ? 'BUY' : 'SELL',
            'price'        => (float) $signal->price,
            'stop_loss'    => $stopLoss,
            'take_profit'  => $takeProfit,
            'is_paper_trade' => true,
        ]);
    }
}
