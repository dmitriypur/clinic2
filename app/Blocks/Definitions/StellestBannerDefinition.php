<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Enums\BlockType;

final class StellestBannerDefinition extends ServiceBannerDefinition
{
    public function type(): BlockType
    {
        return BlockType::BANNER_WITH_BUTTON;
    }

    public function label(): string
    {
        return 'Блок "Баннер для страницы Stellest"';
    }

    public function view(): string
    {
        return 'components.banner.with-btn';
    }
}
