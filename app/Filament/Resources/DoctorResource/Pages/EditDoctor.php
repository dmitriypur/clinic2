<?php

namespace App\Filament\Resources\DoctorResource\Pages;

use App\Filament\Resources\DoctorResource;
use App\Support\BookingPriceSchedule;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Cache;
use SiroDiaz\Redirection\Models\Redirection;

class EditDoctor extends EditRecord
{
    protected static string $resource = DoctorResource::class;

    protected ?bool $hasDatabaseTransactions = true;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['extra'] = BookingPriceSchedule::hydrate($data['extra'] ?? []);
        return DoctorResource::hydrateAgeFields($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $current = $this->record->newQueryWithoutScopes()->whereKey($this->record->getKey())->lockForUpdate()->firstOrFail();
        $data['extra'] = BookingPriceSchedule::save($data['extra'] ?? [], $current->extra ?? [], 'data.extra', true);
        return DoctorResource::dehydrateAgeFields($data);
    }

    protected function getSaveFormAction(): \Filament\Actions\Action
    {
        return parent::getSaveFormAction()
            ->disabled(function (): bool {
                return auth()->user()->hasRole('demo');
            });
    }

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterValidate()
    {
        if (!!$this->data['redirect']) {
            Redirection::create([
                'old_url' => $this->record->handle,
                'new_url' => $this->data['handle']
            ]);
        }
    }

    protected function afterSave()
    {
        Cache::forget('doctors');
        $this->fillForm();
        $this->data['show_redirect'] = false;
        $this->data['redirect'] = false;
    }
}
