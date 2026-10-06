<?php

namespace App\Filament\Resources\CityResource\Pages;

use App\Filament\Resources\CityResource;
use App\Support\BookingPriceForm;
use Filament\Resources\Pages\CreateRecord;

class CreateCity extends CreateRecord
{
    protected static string $resource = CityResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return BookingPriceForm::saveCity($data, []);
    }
}
