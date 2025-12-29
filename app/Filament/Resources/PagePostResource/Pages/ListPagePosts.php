<?php

namespace App\Filament\Resources\PagePostResource\Pages;

use App\Filament\Resources\PagePostResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPagePosts extends ListRecords
{
    protected static string $resource = PagePostResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
