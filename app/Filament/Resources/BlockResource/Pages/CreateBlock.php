<?php

namespace App\Filament\Resources\BlockResource\Pages;

use App\Filament\Resources\BlockResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateBlock extends CreateRecord
{
    protected static string $resource = BlockResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['anchor'] = Str::replace('#', '', $data['anchor']);

        return $data;
    }

    protected function beforeCreate(): void
    {
        app(EditBlock::class)->updateOtherBlocks($this->data);
    }
}
