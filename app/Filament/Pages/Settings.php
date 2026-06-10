<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class Settings extends Page
{
    protected string $view = 'filament.pages.settings';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Settings';

    protected static ?int $navigationSort = 10;

    public bool $emergency_stop    = false;
    public bool $ai_filter_enabled = false;
    public bool $paper_trade       = true;

    public function mount(): void
    {
        $this->emergency_stop    = Setting::isEmergencyStop();
        $this->ai_filter_enabled = Setting::isAiFilterEnabled();
        $this->paper_trade       = (bool) Setting::get('paper_trade', true);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Trading Controls')
                ->description('These settings take effect immediately on the next queued job.')
                ->schema([
                    Toggle::make('emergency_stop')
                        ->label('Emergency Stop')
                        ->helperText('STOP all trading immediately. No new orders will be placed.')
                        ->onColor('danger')
                        ->reactive(),
                    Toggle::make('paper_trade')
                        ->label('Paper Trading Mode')
                        ->helperText('When enabled, orders are logged but NOT sent to Bybit.')
                        ->onColor('warning'),
                    Toggle::make('ai_filter_enabled')
                        ->label('AI Risk Filter')
                        ->helperText('Signals are reviewed by GPT-4o-mini before execution.')
                        ->onColor('info'),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->action('save')
                ->icon(Heroicon::OutlinedCheckCircle),
        ];
    }

    public function save(): void
    {
        Setting::updateOrCreate(['key' => 'emergency_stop'], [
            'value' => $this->emergency_stop ? '1' : '0',
            'type'  => 'boolean',
        ]);
        Setting::updateOrCreate(['key' => 'ai_filter_enabled'], [
            'value' => $this->ai_filter_enabled ? '1' : '0',
            'type'  => 'boolean',
        ]);
        Setting::updateOrCreate(['key' => 'paper_trade'], [
            'value' => $this->paper_trade ? '1' : '0',
            'type'  => 'boolean',
        ]);

        Notification::make()
            ->title('Settings saved successfully')
            ->success()
            ->send();
    }
}
