<?php

namespace App\Filament\Resources\NavigationResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Cache;
use RyanChandler\FilamentNavigation\Filament\Resources\NavigationResource\Pages\Concerns\HandlesNavigationBuilder;
use RyanChandler\FilamentNavigation\FilamentNavigation;

class EditNavigation extends EditRecord
{
    use HandlesNavigationBuilder;

    public static function getResource(): string
    {
        return FilamentNavigation::get()->getResource();
    }

    protected function getFormActions(): array
    {
        return collect(parent::getFormActions())->map(function ($action) {
            $action->disabled(auth()->user()->hasRole('demo'));
            return $action;
        })->all();
    }

    protected function afterSave(): void
    {
        Cache::forget('main-menu');
        Cache::forget('footer-menu');
    }
}
