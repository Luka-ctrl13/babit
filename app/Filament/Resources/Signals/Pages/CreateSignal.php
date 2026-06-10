<?php

namespace App\Filament\Resources\Signals\Pages;

use App\Filament\Resources\Signals\SignalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSignal extends CreateRecord
{
    protected static string $resource = SignalResource::class;
}
