<?php

declare(strict_types=1);

namespace App\Blocks\Contracts;

use App\Enums\BlockType;
use App\Models\Block;

interface BlockDefinition
{
    public function type(): BlockType;

    public function label(): string;

    public function view(): string;

    /**
     * @return array<\Filament\Forms\Components\Component>
     */
    public function formSchema(): array;

    public function viewData(Block $block): array;
}
