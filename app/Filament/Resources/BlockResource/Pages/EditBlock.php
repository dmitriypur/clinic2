<?php

namespace App\Filament\Resources\BlockResource\Pages;

use App\Filament\Resources\BlockResource;
use App\Models\Block;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class EditBlock extends EditRecord
{
    protected static string $resource = BlockResource::class;

    protected function getActions(): array
    {

        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        return collect(parent::getFormActions())->map(function ($action) {
            $action->disabled(auth()->user()->hasRole('demo'));
            return $action;
        })->all();
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['anchor'] = Str::replace('#', '', $data['anchor']);

        return $data;
    }

    public function updateOtherBlocks(array $data): void
    {
        $updateBreadcrumbs = $data['settings']['breadcrumbs'];
        $updateShowPageTitle = $data['settings']['show_page_title'];

        $attributes = [];

        if ($updateBreadcrumbs) {
            $attributes['settings->breadcrumbs'] = false;
        }

        if ($updateShowPageTitle) {
            $attributes['settings->show_page_title'] = false;
        }

        if (count($attributes)) {
            Block::query()
                ->where('page_id', $data['page_id'])
                ->where('id', '!=', $data['id'])
                ->update($attributes);
        }
    }


    protected function beforeSave(): void
    {
        $this->updateOtherBlocks($this->data);
    }
}
