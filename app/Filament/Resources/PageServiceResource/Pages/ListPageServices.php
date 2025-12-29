<?php

namespace App\Filament\Resources\PageServiceResource\Pages;

use App\Filament\Resources\PageServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPageServices extends ListRecords
{
    protected static string $resource = PageServiceResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
