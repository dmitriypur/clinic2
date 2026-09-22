<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;
use App\Models\Block;

final class KidsOpticsCallbackDefinition extends AbstractBlockDefinition
{
    public function type(): BlockType
    {
        return BlockType::KIDS_OPTICS_CALLBACK;
    }

    public function label(): string
    {
        return 'Блок «Подбор очков и обратный звонок»';
    }

    public function view(): string
    {
        return 'components.block.kids-optics-callback';
    }

    public function formSchema(): array
    {
        return [];
    }

    public function viewData(Block $block): array
    {
        return [
            'callbackId' => 'kids-optics-callback-'.($block->getKey() ?? 'preview'),
            'desktopCharacter' => asset('images/kids-optics/callback/desktop-character.webp'),
            'mobileCharacter' => asset('images/kids-optics/callback/mobile-character.webp'),
            'desktopPattern' => asset('images/kids-optics/callback/desktop-pattern.svg'),
            'mobilePattern' => asset('images/kids-optics/callback/mobile-pattern.svg'),
        ];
    }
}
