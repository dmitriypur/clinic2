<?php

namespace App\Filament\Resources\DoctorResource\Pages;

use App\Filament\Resources\DoctorResource;
use App\Support\BookingPriceSchedule;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDoctor extends CreateRecord
{
    protected static string $resource = DoctorResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['extra'] = BookingPriceSchedule::save($data['extra'] ?? [], [], 'data.extra', true);
        return DoctorResource::dehydrateAgeFields($data);
    }
}
