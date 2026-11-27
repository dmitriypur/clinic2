<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Enums\BlockType;

final class CorrectionBannerDefinition extends ServiceBannerDefinition
{
    public function type(): BlockType
    {
        return BlockType::BANNER_CORRECTION;
    }

    public function label(): string
    {
        return 'Блок "Баннер для страницы Коррекция астигматизма"';
    }

    public function view(): string
    {
        return 'components.banner.correction';
    }
}
