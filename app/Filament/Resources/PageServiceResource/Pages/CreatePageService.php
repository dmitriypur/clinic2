<?php

namespace App\Filament\Resources\PageServiceResource\Pages;

use App\Filament\Resources\PageServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePageService extends CreateRecord
{
    protected static string $resource = PageServiceResource::class;
}
