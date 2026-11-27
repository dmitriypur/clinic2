<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;

final class SpecialistCallbackDefinition extends AbstractBlockDefinition
{
    public function type(): BlockType
    {
        return BlockType::BANNER_SPECIALIST_CALLBACK;
    }

    public function label(): string
    {
        return 'Блок "Запись или обратный звонок"';
    }

    public function view(): string
    {
        return 'components.banner.specialist-callback';
    }

    public function formSchema(): array
    {
        return [];
    }
}
