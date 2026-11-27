<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Enums\BlockType;

final class AppointmentBannerDefinition extends ServiceBannerDefinition
{
    public function type(): BlockType
    {
        return BlockType::BANNER_APPOINTMENT;
    }

    public function label(): string
    {
        return 'Блок "Баннер для страницы Приём детского офтальмолога"';
    }

    public function view(): string
    {
        return 'components.banner.appointment';
    }
}
