<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BlockType;
use App\Enums\PageType;
use App\Models\Block;
use App\Models\Page;

class ArticleNavigationBlockService
{
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

        $this->positionExistingForPage($page);

        return $block;
    }

    public function positionExistingForPage(Page $page): void
    {
        if ($page->type !== PageType::Posts) {
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

        Block::setNewOrder(
            $orderedBlocks->pluck('id')->all(),
            1,
            null,
            fn($query) => $query
                ->withoutGlobalScopes()
                ->where('page_id', $page->id),
        );
    }
}
