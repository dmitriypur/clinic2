<?php

namespace App\Filament\Pages;

use App\Models\City;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class BookingWidgetSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationGroup = 'Настройки';

    protected static ?string $navigationLabel = 'Настройки виджета';

    protected static ?string $title = 'Настройки виджета';

    protected static ?int $navigationSort = 8;

    protected static string $view = 'filament.pages.booking-widget-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $defaultCityId = City::query()
            ->where('active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->value('id');

        $this->form->fill([
            'city_id' => $defaultCityId,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('city_id')
                    ->label('Город')
                    ->options(
                        City::query()
                            ->where('active', true)
                            ->orderByDesc('is_default')
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all()
                    )
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
}
