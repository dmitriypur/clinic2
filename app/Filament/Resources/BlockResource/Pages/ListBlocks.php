<?php

namespace App\Filament\Resources\BlockResource\Pages;

use App\Blocks\BlockRegistry;
use App\Filament\Resources\BlockResource;
use App\Models\Block;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListBlocks extends ListRecords
{
    protected static string $resource = BlockResource::class;

    public function getHeader(): View
    {
        $usedTypes = Block::query()
            ->withoutGlobalScope('city')
            ->distinct()
            ->toBase()
            ->pluck('type')
            ->all();
        $types = app(BlockRegistry::class)->options();

        return view('filament.resources.block-resource.list-header', [
            'unusedTypes' => array_diff_key($types, array_flip($usedTypes)),
        ]);
    }

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
