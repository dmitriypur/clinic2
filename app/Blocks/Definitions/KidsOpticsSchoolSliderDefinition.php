<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;
use App\Models\Block;
use App\Services\FrameCatalogService;

final class KidsOpticsSchoolSliderDefinition extends AbstractBlockDefinition
{
    private const DEFAULT_TITLE = 'Отличный выбор для школы';

    public function __construct(private readonly FrameCatalogService $catalog)
    {
    }

    public function type(): BlockType
    {
        return BlockType::KIDS_OPTICS_SCHOOL_SLIDER;
    }

    public function label(): string
    {
        return 'Отличный выбор для школы «Детская оптика»';
    }

    public function view(): string
    {
        return 'components.block.kids-optics-school-slider';
    }

    public function formSchema(): array
    {
        return [];
    }

    public function wrapperClassName(Block $block): ?string
    {
        return 'bg-surface-subdued py-8 md:py-12 xl:py-16';
    }

    public function viewData(Block $block): array
    {
        return [
            'sliderTitle' => trim((string) $block->title) ?: self::DEFAULT_TITLE,
            'frames' => $this->catalog->schoolSlider(),
        ];
    }
}
