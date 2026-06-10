<?php

namespace App\Filament\Resources\Signals\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SignalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')->sortable()->label('ID'),
                TextColumn::make('action')
                    ->badge()
                    ->color(fn (string $state) => $state === 'buy' ? 'success' : 'danger')
                    ->searchable(),
                TextColumn::make('symbol')->searchable()->sortable(),
                TextColumn::make('price')->numeric(8)->sortable(),
                TextColumn::make('volume')->numeric(8)->sortable(),
                TextColumn::make('stop_loss_pct')->label('SL %')->numeric(2)->sortable(),
                TextColumn::make('take_profit_pct')->label('TP %')->numeric(2)->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'executed'           => 'success',
                        'pending',
                        'processing'         => 'warning',
                        'rejected_by_ai',
                        'rejected_emergency',
                        'failed'             => 'danger',
                        default              => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('ai_decision')
                    ->badge()
                    ->color(fn (?string $state) => $state === 'approved' ? 'success' : 'danger')
                    ->label('AI'),
                TextColumn::make('source_ip')->label('IP'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending'             => 'Pending',
                        'executed'            => 'Executed',
                        'rejected_by_ai'      => 'Rejected by AI',
                        'rejected_emergency'  => 'Emergency Stop',
                        'failed'              => 'Failed',
                    ]),
                SelectFilter::make('action')
                    ->options(['buy' => 'Buy', 'sell' => 'Sell']),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
