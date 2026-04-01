<?php

namespace App\Filament\Resources\CityResource\Pages;

use App\Filament\Resources\CityResource;
use App\Services\UtmTrackerService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCity extends EditRecord
{
    protected static string $resource = CityResource::class;

    protected array $utmTrackerState = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['utm_tracker'] = app(UtmTrackerService::class)->getEditorState($this->getRecord());

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->utmTrackerState = $data['utm_tracker'] ?? [];
        unset($data['utm_tracker']);

        return $data;
    }

    protected function afterSave(): void
    {
        app(UtmTrackerService::class)->sync($this->getRecord()->fresh(), $this->utmTrackerState);
    }
}
