<?php

namespace App\Filament\Resources\CityResource\Pages;

use App\Filament\Resources\CityResource;
use App\Services\UtmTrackerService;
use Filament\Resources\Pages\CreateRecord;

class CreateCity extends CreateRecord
{
    protected static string $resource = CityResource::class;

    protected array $utmTrackerState = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->utmTrackerState = $data['utm_tracker'] ?? [];
        unset($data['utm_tracker']);

        return $data;
    }

    protected function afterCreate(): void
    {
        app(UtmTrackerService::class)->sync($this->getRecord()->fresh(), $this->utmTrackerState);
    }
}
