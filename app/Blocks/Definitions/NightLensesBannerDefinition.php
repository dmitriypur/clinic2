<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Enums\BlockType;

final class NightLensesBannerDefinition extends ServiceBannerDefinition
{
    public function type(): BlockType
    {
        return BlockType::BANNER_NIGHT_LENSES;
    }

    public function label(): string
    {
        return 'Блок "Баннер для страницы Ночные линзы"';
    }

    public function view(): string
    {
        return 'components.night-lenses.banner';
    }
}
