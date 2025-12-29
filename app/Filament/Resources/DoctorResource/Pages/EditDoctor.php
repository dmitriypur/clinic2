<?php

namespace App\Filament\Resources\DoctorResource\Pages;

use App\Filament\Resources\DoctorResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Cache;
use SiroDiaz\Redirection\Models\Redirection;

class EditDoctor extends EditRecord
{
    protected static string $resource = DoctorResource::class;

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
        $this->data['show_redirect'] = false;
        $this->data['redirect'] = false;
    }
}
