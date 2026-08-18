<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;

final class ReceptionStepsDefinition extends AbstractBlockDefinition
{
    public function type(): BlockType
    {
        return BlockType::RECEPTION_STEPS;
    }

    public function label(): string
    {
        return 'Этапы приема';
    }

    public function view(): string
    {
        return 'components.block.reception-steps';
    }

    public function formSchema(): array
    {
        return [];
    }
}
