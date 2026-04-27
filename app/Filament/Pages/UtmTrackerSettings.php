<?php

namespace App\Filament\Pages;

use App\Models\City;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class UtmTrackerSettings extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationGroup = 'Настройки';

    protected static ?string $navigationLabel = 'UTM Трекер';

    protected static ?string $title = 'UTM Трекер';

    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.pages.utm-tracker-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'city_id' => $this->getDefaultCityId(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('city_id')
                    ->label('Город')
                    ->options(fn (): array => City::query()
                        ->orderByDesc('is_default')
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->native(false)
                    ->searchable()
                    ->live()
                    ->required(),
            ])
            ->statePath('data');
    }

    public function getSelectedCityId(): ?int
    {
        $cityId = data_get($this->data, 'city_id');

        return is_numeric($cityId) ? (int) $cityId : null;
    }

    public function getSelectedCityName(): ?string
    {
        $cityId = $this->getSelectedCityId();

        if (! $cityId) {
            return null;
        }

        return City::query()
            ->whereKey($cityId)
            ->value('name');
    }

    private function getDefaultCityId(): ?int
    {
        return City::query()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->value('id');
    }
}
