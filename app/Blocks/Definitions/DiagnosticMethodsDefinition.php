<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;

final class DiagnosticMethodsDefinition extends AbstractBlockDefinition
{
    public function type(): BlockType
    {
        return BlockType::DIAGNOSTIC_METHODS;
    }

    public function label(): string
    {
        return 'Методы диагностики';
    }

    public function view(): string
    {
        return 'components.block.diagnostic-methods';
    }

    public function formSchema(): array
    {
        return [];
    }
}
