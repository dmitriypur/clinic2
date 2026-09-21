<?php

namespace App\Filament\Resources\FrameAgeGroupResource\Pages;

use App\Filament\Resources\FrameAgeGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFrameAgeGroups extends ListRecords
{
    protected static string $resource = FrameAgeGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
