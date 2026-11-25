<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Enums\BlockType;

final class VideoNewDefinition extends VideoDefinition
{
    public function type(): BlockType
    {
        return BlockType::VIDEO_NEW;
    }

    public function label(): string
    {
        return 'Видео новый дизайн';
    }

    public function view(): string
    {
        return 'components.block.video-new';
    }
}
