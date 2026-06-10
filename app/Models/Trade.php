<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trade extends Model
{
    protected $fillable = [
        'signal_id',
        'bybit_order_id',
        'bybit_order_link_id',
        'symbol',
        'side',
        'order_type',
        'qty',
        'price',
        'stop_loss',
        'take_profit',
        'status',
        'bybit_response',
        'is_paper_trade',
    ];

    protected $casts = [
        'bybit_response' => 'array',
        'qty' => 'decimal:8',
        'price' => 'decimal:8',
        'stop_loss' => 'decimal:8',
        'take_profit' => 'decimal:8',
        'is_paper_trade' => 'boolean',
    ];

    public function signal()
    {
        return $this->belongsTo(Signal::class);
    }
}
