<?php

namespace App\Filament\Resources\TagResource\Pages;

use App\Filament\Resources\TagResource;
use App\Models\Tag;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditTag extends EditRecord
{
    protected static string $resource = TagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function (Tag $record, Actions\DeleteAction $action): void {
                    if (! $record->pages()->exists()) {
                        return;
                    }

                    Notification::make()
                        ->danger()
                        ->title('Нельзя удалить тег')
                        ->body('Тег привязан к странице. Сначала удалите эту связь.')
                        ->send();

                    $action->halt();
                }),
        ];
    }
}
