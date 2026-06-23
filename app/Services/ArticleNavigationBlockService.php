<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BlockType;
use App\Enums\PageType;
use App\Models\Block;
use App\Models\Page;
use Closure;

class ArticleNavigationBlockService
{
    /** @var array<int, int> */
    private array $deferredPageIds = [];

    public function syncForPage(Page $page): ?Block
    {
        if ($page->type === PageType::Posts) {
            return $this->ensureForPage($page);
        }

        Block::query()
            ->withoutGlobalScopes()
            ->where('page_id', $page->id)
            ->where('type', BlockType::ARTICLE_NAVIGATION)
            ->get()
            ->each
            ->delete();

        return null;
    }

    public function ensureForPage(Page $page): ?Block
    {
        if ($page->type !== PageType::Posts) {
            return null;
        }

        $block = Block::query()
            ->withoutGlobalScopes()
            ->firstOrCreate([
                'page_id' => $page->id,
                'type' => BlockType::ARTICLE_NAVIGATION,
            ], [
                'title' => 'Навигация по статьям',
                'payload' => [],
            ]);

        if (! $block->wasRecentlyCreated) {
            $this->positionExistingForPage($page);
        }

        return $block;
    }

    public function positionExistingForPage(Page $page): void
    {
        if (
            $page->type !== PageType::Posts
            || $this->isPositioningDeferredFor($page->id)
        ) {
            return;
        }

        $blocks = Block::query()
            ->withoutGlobalScopes()
            ->where('page_id', $page->id)
            ->orderBy('order_column')
            ->get();

        $navigation = $blocks->firstWhere('type', BlockType::ARTICLE_NAVIGATION);

        if (! $navigation) {
            return;
        }

        $orderedBlocks = $blocks
            ->reject(fn(Block $block): bool => $block->is($navigation))
            ->values();

        $faqIndex = $orderedBlocks->search(
            fn(Block $block): bool => $block->type === BlockType::FAQ
        );

        if ($faqIndex !== false) {
            $orderedBlocks->splice($faqIndex, 0, [$navigation]);
        } else {
            $lastContentIndex = $orderedBlocks
                ->keys()
                ->filter(fn(int $index): bool => in_array(
                    $orderedBlocks[$index]->type,
                    [BlockType::POST_TEXT, BlockType::EXPERT_OPINION],
                    true,
                ))
                ->last();

            if ($lastContentIndex === null) {
                $orderedBlocks->push($navigation);
            } else {
                $orderedBlocks->splice($lastContentIndex + 1, 0, [$navigation]);
            }
        }

        $currentOrder = $blocks->pluck('id')->all();
        $newOrder = $orderedBlocks->pluck('id')->all();
        $orderColumnsAreNormalized = $blocks->values()->every(
            fn (Block $block, int $index): bool => $block->order_column === $index + 1,
        );

        if ($currentOrder === $newOrder && $orderColumnsAreNormalized) {
            return;
        }

        Block::setNewOrder(
            $newOrder,
            1,
            null,
            fn($query) => $query
                ->withoutGlobalScopes()
                ->where('page_id', $page->id),
        );
    }

    public function deferPositioning(Page $page, Closure $callback): mixed
    {
        $pageId = (int) $page->id;
        $this->deferredPageIds[$pageId] = ($this->deferredPageIds[$pageId] ?? 0) + 1;
        $completed = false;

        try {
            $result = $callback();
            $completed = true;

            return $result;
        } finally {
            $this->deferredPageIds[$pageId]--;

            if ($this->deferredPageIds[$pageId] === 0) {
                unset($this->deferredPageIds[$pageId]);

                if ($completed) {
                    $this->positionExistingForPage($page);
                }
            }
        }
    }

    public function isPositioningDeferredFor(int $pageId): bool
    {
        return isset($this->deferredPageIds[$pageId]);
    }
}
