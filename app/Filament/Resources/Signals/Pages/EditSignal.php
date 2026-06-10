<?php

namespace App\Filament\Resources\Signals\Pages;

use App\Filament\Resources\Signals\SignalResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSignal extends EditRecord
{
    protected static string $resource = SignalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
