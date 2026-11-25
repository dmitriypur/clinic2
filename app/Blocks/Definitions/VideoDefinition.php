<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;
use App\Filament\Forms\Components\SafeMediaLibraryFileUpload;

class VideoDefinition extends AbstractBlockDefinition
{
    public function type(): BlockType
    {
        return BlockType::VIDEO;
    }

    public function label(): string
    {
        return 'Видео';
    }

    public function view(): string
    {
        return 'components.block.video';
    }

    public function formSchema(): array
    {
        return [
            SafeMediaLibraryFileUpload::make('video')
                ->safeVideos()
                ->collection('video')
                ->label('Видео')
                ->openable(),

            SafeMediaLibraryFileUpload::make('cover')
                ->safeImages()
                ->collection('cover')
                ->label('Обложка видео')
                ->imageEditor()
                ->openable(),
        ];
    }
}
