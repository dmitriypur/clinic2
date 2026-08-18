<?php

declare(strict_types=1);

namespace App\Blocks;

use App\Blocks\Contracts\BlockDefinition;
use App\Models\Block;

abstract class AbstractBlockDefinition implements BlockDefinition
{
    public function viewData(Block $block): array
    {
        return [];
    }
}
