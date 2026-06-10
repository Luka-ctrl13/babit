<?php

namespace App\Filament\Resources\Signals\Pages;

use App\Filament\Resources\Signals\SignalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSignals extends ListRecords
{
    protected static string $resource = SignalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
