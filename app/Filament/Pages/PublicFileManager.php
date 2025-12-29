<?php

namespace App\Filament\Pages;

use BostjanOb\FilamentFileManager\Pages\FileManager;

class PublicFileManager extends FileManager
{
    protected static ?string $navigationLabel = 'Файлы';

    protected string $disk = 'public';
}
