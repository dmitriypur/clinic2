<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Block;
use App\Services\PageService;

class BlockObserver
{
    public function __construct(
        private readonly PageService $pageService
    ) {}

    /**
     * Handle the Block "created" event.
     */
    public function created(Block $block): void
    {
        $this->clearRelatedPageCache($block);
    }

    /**
     * Handle the Block "updated" event.
     */
    public function updated(Block $block): void
    {
        $this->clearRelatedPageCache($block);
    }

    /**
     * Handle the Block "deleted" event.
     */
    public function deleted(Block $block): void
    {
        $this->clearRelatedPageCache($block);
    }

    /**
     * Handle the Block "restored" event.
     */
    public function restored(Block $block): void
    {
        $this->clearRelatedPageCache($block);
    }

    /**
     * Handle the Block "force deleted" event.
     */
    public function forceDeleted(Block $block): void
    {
        $this->clearRelatedPageCache($block);
    }

    private function clearRelatedPageCache(Block $block): void
    {
        $pages = collect();

        if ($block->relationLoaded('page')) {
            $pages->push($block->page);
        } else {
            $pages->push($block->page()->with('category')->first());
        }

        $block->loadMissing('pages.category');
        $pages = $pages->merge($block->pages);

        $pages
            ->filter()
            ->unique('id')
            ->each(fn ($page) => $this->pageService->clearPageCache($page));
    }
}
