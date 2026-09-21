<?php

namespace App\Filament\Resources\FrameBrandResource\Pages;

use App\Filament\Resources\FrameBrandResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFrameBrands extends ListRecords
{
    protected static string $resource = FrameBrandResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
