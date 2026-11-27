<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Enums\BlockType;

final class SelectionGlassesBannerDefinition extends ServiceBannerDefinition
{
    public function type(): BlockType
    {
        return BlockType::BANNER_SELECTION_GLASSES;
    }

    public function label(): string
    {
        return 'Блок "Баннер для страницы Подбор очков"';
    }

    public function view(): string
    {
        return 'components.banner.selection-glasses';
    }
}
