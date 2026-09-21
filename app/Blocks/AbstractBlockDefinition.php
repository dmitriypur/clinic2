<?php

declare(strict_types=1);

namespace App\Blocks;

use App\Blocks\Contracts\BlockDefinition;
use App\Models\Block;

abstract class AbstractBlockDefinition implements BlockDefinition
{
    public function wrapperClassName(Block $block): ?string
    {
        return null;
    }

    public function viewData(Block $block): array
    {
        return [];
    }
}
