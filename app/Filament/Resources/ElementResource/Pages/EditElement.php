<?php

namespace App\Filament\Resources\ElementResource\Pages;

use App\Filament\Resources\ElementResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditElement extends EditRecord
{
    protected static string $resource = ElementResource::class;

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
}
