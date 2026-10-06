<?php

namespace App\Filament\Resources\CityResource\Pages;

use App\Filament\Resources\CityResource;
use App\Support\BookingPriceForm;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCity extends EditRecord
{
    protected static string $resource = CityResource::class;

    protected ?bool $hasDatabaseTransactions = true;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return BookingPriceForm::hydrateCity($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $current = $this->record->newQueryWithoutScopes()->whereKey($this->record->getKey())->lockForUpdate()->firstOrFail();

        return BookingPriceForm::saveCity($data, $current->branches ?? []);
    }

    protected function afterSave(): void
    {
        $this->fillForm();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
