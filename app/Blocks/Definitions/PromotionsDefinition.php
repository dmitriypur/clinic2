<?php

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;
use App\Models\Block;
use App\Services\PromotionBlockService;

final class PromotionsDefinition extends AbstractBlockDefinition
{
    public function __construct(private readonly PromotionBlockService $promotions) {}

    public function type(): BlockType
    {
        return BlockType::PROMOTIONS;
    }

    public function label(): string
    {
        return 'Акции';
    }

    public function view(): string
    {
        return 'components.block.promotions';
    }

    public function formSchema(): array
    {
        return [];
    }

    public function viewData(Block $block): array
    {
        return ['promotions' => $this->promotions->forCurrentCity()];
    }
}
