<?php

namespace App\Filament\Resources\FrameColorResource\Pages;

use App\Filament\Resources\FrameColorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFrameColors extends ListRecords
{
    protected static string $resource = FrameColorResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
