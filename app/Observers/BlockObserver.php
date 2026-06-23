<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Block;
use App\Models\Page;
use App\Services\ArticleNavigationBlockService;
use App\Services\PageService;
use Illuminate\Support\Facades\Cache;

class BlockObserver
{
    public function __construct(
        private readonly PageService $pageService,
        private readonly ArticleNavigationBlockService $articleNavigationBlockService,
    ) {}

    /**
     * Handle the Block "created" event.
     */
    public function created(Block $block): void
    {
        $this->positionArticleNavigation($block);
        $this->clearRelatedPageCache($block);
    }

    /**
     * Handle the Block "updated" event.
     */
    public function updated(Block $block): void
    {
        if ($block->wasChanged(['page_id', 'type', 'order_column'])) {
            $this->positionArticleNavigation($block);

            $originalPageId = (int) $block->getOriginal('page_id');
            if ($originalPageId && $originalPageId !== (int) $block->page_id) {
                $this->positionArticleNavigationForPageId($originalPageId);
            }
        }

        $this->clearRelatedPageCache($block, true);
    }

    /**
     * Handle the Block "deleted" event.
     */
    public function deleted(Block $block): void
    {
        $this->positionArticleNavigation($block);
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

    private function clearRelatedPageCache(Block $block, bool $includeOriginalPage = false): void
    {
        Cache::forget('services_with_media_and_prices');

        $pages = collect();

        if ($block->relationLoaded('page')) {
            $pages->push($block->page);
        } else {
            $pages->push($block->page()->with('category')->first());
        }

        $block->loadMissing('pages.category');
        $pages = $pages->merge($block->pages);

        if ($includeOriginalPage && $block->wasChanged('page_id')) {
            $pages->push(
                Page::query()
                    ->withoutGlobalScopes()
                    ->with('category')
                    ->find($block->getOriginal('page_id'))
            );
        }

        $pages
            ->filter()
            ->unique('id')
            ->reject(
                fn ($page) => $this->articleNavigationBlockService
                    ->isPositioningDeferredFor((int) $page->id)
            )
            ->each(fn ($page) => $this->pageService->clearPageCache($page));
    }

    private function positionArticleNavigation(Block $block): void
    {
        $this->positionArticleNavigationForPageId((int) $block->page_id);
    }

    private function positionArticleNavigationForPageId(int $pageId): void
    {
        if (! $pageId || $this->articleNavigationBlockService->isPositioningDeferredFor($pageId)) {
            return;
        }

        $page = Page::query()
            ->withoutGlobalScopes()
            ->find($pageId);

        if ($page) {
            $this->articleNavigationBlockService->positionExistingForPage($page);
        }
    }
}
