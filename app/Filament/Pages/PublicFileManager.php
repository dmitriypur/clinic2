<?php

namespace App\Filament\Pages;

use BostjanOb\FilamentFileManager\Pages\FileManager;
use Filament\Facades\Filament;

class PublicFileManager extends FileManager
{
    protected static ?string $navigationLabel = 'Файлы';

    protected string $disk = 'public';

    public static function canAccess(): bool
    {
        return Filament::auth()->user()?->can('page_PublicFileManager') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
