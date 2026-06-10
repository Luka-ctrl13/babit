<?php

namespace App\Filament\Resources\Trades\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TradeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('signal_id')
                    ->required()
                    ->numeric(),
                TextInput::make('bybit_order_id'),
                TextInput::make('bybit_order_link_id'),
                TextInput::make('symbol')
                    ->required(),
                TextInput::make('side')
                    ->required(),
                TextInput::make('order_type')
                    ->required()
                    ->default('Market'),
                TextInput::make('qty')
                    ->required()
                    ->numeric(),
                TextInput::make('price')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('stop_loss')
                    ->numeric(),
                TextInput::make('take_profit')
                    ->numeric(),
                TextInput::make('status'),
                Textarea::make('bybit_response')
                    ->columnSpanFull(),
                Toggle::make('is_paper_trade')
                    ->required(),
            ]);
    }
}
