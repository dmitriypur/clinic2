<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;
use App\Filament\Forms\Components\SafeMediaLibraryFileUpload;
use Filament\Forms;

final class VideoCarouselDefinition extends AbstractBlockDefinition
{
    public function type(): BlockType
    {
        return BlockType::VIDEO_CAROUSEL;
    }

    public function label(): string
    {
        return 'Видео карусель';
    }

    public function view(): string
    {
        return 'components.block.video-carousel';
    }

    public function formSchema(): array
    {
        return [
            Forms\Components\Section::make([
                SafeMediaLibraryFileUpload::make('videos')
                    ->safeVideos()
                    ->collection('videos')
                    ->label('Вертикальные видео')
                    ->multiple()
                    ->required(),
            ]),
        ];
    }
}
