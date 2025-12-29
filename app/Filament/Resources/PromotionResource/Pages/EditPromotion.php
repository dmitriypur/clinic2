<?php

namespace App\Filament\Resources\PromotionResource\Pages;

use App\Filament\Resources\PromotionResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPromotion extends EditRecord
{
    protected static string $resource = PromotionResource::class;

    protected function afterSave(): void
    {
        PromotionResource::forgetPromotionsCache();
    }

    protected function getSaveFormAction(): \Filament\Actions\Action
    {
        return parent::getSaveFormAction()
            ->disabled(function (): bool {
                return auth()->user()->hasRole('demo');
            });
    }
    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->after(fn () => PromotionResource::forgetPromotionsCache()),
        ];
    }
}
