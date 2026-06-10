<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function receive(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'action'          => 'required|in:buy,sell,Buy,Sell',
            'symbol'          => 'required|string|max:20',
            'price'           => 'nullable|numeric|min:0',
            'volume'          => 'required|numeric|min:0.00000001',
            'stop_loss_pct'   => 'nullable|numeric|min:0|max:100',
            'take_profit_pct' => 'nullable|numeric|min:0|max:100',
        ]);

        $signal = \App\Models\Signal::create([
            'action'          => strtolower($validated['action']),
            'symbol'          => strtoupper($validated['symbol']),
            'price'           => $validated['price'] ?? null,
            'volume'          => $validated['volume'],
            'stop_loss_pct'   => $validated['stop_loss_pct'] ?? null,
            'take_profit_pct' => $validated['take_profit_pct'] ?? null,
            'source_ip'       => $request->ip(),
            'raw_payload'     => $request->except('passphrase'),
        ]);

        \App\Jobs\ProcessTradingSignal::dispatch($signal->id);

        return response()->json(['status' => 'queued', 'signal_id' => $signal->id], 200);
    }
}
