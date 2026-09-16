<?php

namespace App\Filament\Pages;

use App\Filament\Forms\Components\SafeFileUpload;
use BostjanOb\FilamentFileManager\Model\FileItem;
use BostjanOb\FilamentFileManager\Pages\FileManager;
use Filament\Facades\Filament;
use Filament\Tables\Table;

class PublicFileManager extends FileManager
{
    public const MANAGE_PERMISSION = 'manage_PublicFileManager';

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

    public static function canManageFiles(): bool
    {
        $user = Filament::auth()->user();

        return ($user?->can('page_PublicFileManager') ?? false)
            && ($user?->can(static::MANAGE_PERMISSION) ?? false)
            && ! ($user?->hasRole('demo') ?? false);
    }

    public function table(Table $table): Table
    {
        $table = parent::table($table);
        $canManageFiles = fn (): bool => static::canManageFiles();

        foreach ($table->getActions() as $action) {
            if ($action->getName() === 'delete') {
                $action->authorize($canManageFiles);
            }
        }

        foreach ($table->getBulkActions() as $action) {
            if ($action->getName() === 'delete') {
                $action->authorize($canManageFiles);
            }
        }

        foreach ($table->getHeaderActions() as $action) {
            if (! in_array($action->getName(), ['create_folder', 'upload_file'], true)) {
                continue;
            }

            $action->authorize($canManageFiles);

            if ($action->getName() === 'upload_file') {
                $action->form([
                    SafeFileUpload::make('files')
                        ->required()
                        ->multiple()
                        ->previewable(false)
                        ->preserveFilenames()
                        ->safePublicFiles()
                        ->disk($this->disk)
                        ->directory($this->path),
                ]);
            }
        }

        return $table->checkIfRecordIsSelectableUsing(
            fn (FileItem $record): bool => static::canManageFiles() && ! $record->isPreviousPath()
        );
    }
}
