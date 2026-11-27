<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Enums\BlockType;

final class MyopiaBannerDefinition extends ServiceBannerDefinition
{
    public function type(): BlockType
    {
        return BlockType::BANNER_MYOPIA;
    }

    public function label(): string
    {
        return 'Блок "Баннер для страницы Лечение близорукости"';
    }

    public function view(): string
    {
        return 'components.banner.myopia';
    }
}
