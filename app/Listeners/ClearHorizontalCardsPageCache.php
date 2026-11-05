<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\BlockType;
use App\Models\Block;
use App\Services\PageService;
use Spatie\MediaLibrary\Conversions\Events\ConversionHasBeenCompleted;

final class ClearHorizontalCardsPageCache
{
    public function __construct(private readonly PageService $pages) {}

    public function handle(ConversionHasBeenCompleted $event): void
    {
        if ($event->conversion->getName() !== 'main' || $event->media->model_type !== (new Block)->getMorphClass()) {
            return;
        }

        $block = Block::withoutGlobalScopes()
            ->where('type', BlockType::CARDS_ITEM_ROW->value)
            ->with(['page.category', 'pages.category'])
            ->find($event->media->model_id);

        if (! $block) {
            return;
        }

        $block->pages->push($block->page)
            ->filter()
            ->unique('id')
            ->each(fn ($page) => $this->pages->clearPageCache($page));
    }
}
