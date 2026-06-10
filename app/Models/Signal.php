<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Signal extends Model
{
    protected $fillable = [
        'action',
        'symbol',
        'price',
        'volume',
        'stop_loss_pct',
        'take_profit_pct',
        'status',
        'ai_decision',
        'ai_reason',
        'source_ip',
        'raw_payload',
        'error_message',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'price' => 'decimal:8',
        'volume' => 'decimal:8',
        'stop_loss_pct' => 'decimal:4',
        'take_profit_pct' => 'decimal:4',
    ];

    public function trade()
    {
        return $this->hasOne(Trade::class);
    }

    public function isExecuted(): bool
    {
        return $this->status === 'executed';
    }

    public function markFailed(string $message): void
    {
        $this->update(['status' => 'failed', 'error_message' => $message]);
    }
}
