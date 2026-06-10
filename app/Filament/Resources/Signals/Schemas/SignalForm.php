<?php

namespace App\Filament\Resources\Signals\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class SignalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('action')
                    ->required(),
                TextInput::make('symbol')
                    ->required(),
                TextInput::make('price')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('volume')
                    ->required()
                    ->numeric(),
                TextInput::make('stop_loss_pct')
                    ->numeric(),
                TextInput::make('take_profit_pct')
                    ->numeric(),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                TextInput::make('ai_decision'),
                Textarea::make('ai_reason')
                    ->columnSpanFull(),
                TextInput::make('source_ip'),
                Textarea::make('raw_payload')
                    ->columnSpanFull(),
                Textarea::make('error_message')
                    ->columnSpanFull(),
            ]);
    }
}
