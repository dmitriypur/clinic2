<?php

namespace App\Filament\Resources\DoctorResource\Pages;

use App\Filament\Resources\DoctorResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDoctor extends CreateRecord
{
    protected static string $resource = DoctorResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return DoctorResource::dehydrateAgeFields($data);
    }
}
