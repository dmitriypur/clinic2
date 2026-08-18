<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;

final class TreatmentMethodsDefinition extends AbstractBlockDefinition
{
    public function type(): BlockType
    {
        return BlockType::TREATMENT_METHODS;
    }

    public function label(): string
    {
        return 'Методы лечения';
    }

    public function view(): string
    {
        return 'components.block.treatment-methods';
    }

    public function formSchema(): array
    {
        return [];
    }
}
