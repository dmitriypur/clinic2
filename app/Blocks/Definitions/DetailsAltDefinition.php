<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;

final class DetailsAltDefinition extends AbstractBlockDefinition
{
    public function type(): BlockType
    {
        return BlockType::DETAILS_ALT;
    }

    public function label(): string
    {
        return 'Реквизиты карточки';
    }

    public function view(): string
    {
        return 'components.block.details-alt';
    }

    public function formSchema(): array
    {
        return [];
    }
}
