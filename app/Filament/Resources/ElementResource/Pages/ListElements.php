<?php

namespace App\Filament\Resources\ElementResource\Pages;

use App\Filament\Resources\ElementResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListElements extends ListRecords
{
    protected static string $resource = ElementResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
