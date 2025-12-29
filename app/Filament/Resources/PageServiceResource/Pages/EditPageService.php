<?php

namespace App\Filament\Resources\PageServiceResource\Pages;

use App\Filament\Resources\PageServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Cache;
use SiroDiaz\Redirection\Models\Redirection;

class EditPageService extends EditRecord
{
    protected static string $resource = PageServiceResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getSaveFormAction(): \Filament\Actions\Action
    {
        return parent::getSaveFormAction()
            ->disabled(function (): bool {
                return auth()->user()->hasRole('demo');
            });
    }

    protected function afterValidate()
    {
        if (!!$this->data['redirect']) {
            Redirection::create([
                'old_url' => $this->record->handle,
                'new_url' => $this->data['handle']
            ]);
        }
    }

    protected function afterSave()
    {
        Cache::forget('page-'.$this->data['handle']);
        $this->data['show_redirect'] = false;
        $this->data['redirect'] = false;
    }
}
