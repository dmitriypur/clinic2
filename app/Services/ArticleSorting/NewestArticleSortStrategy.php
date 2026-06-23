<?php

namespace App\Services\ArticleSorting;

use App\Contracts\ArticleSortStrategy;
use App\Enums\PageType;
use App\Models\Page;
use App\Support\ArticleNeighbors;
use Illuminate\Database\Eloquent\Builder;

class NewestArticleSortStrategy implements ArticleSortStrategy
{
    public function apply(Builder $query): Builder
    {
        return $this->activePosts($query)
            ->reorder()
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function neighbors(Page $page): ArticleNeighbors
    {
        if ($page->type !== PageType::Posts || ! $page->active || ! $page->category_id) {
            return new ArticleNeighbors(null, null);
        }

        $baseQuery = fn (): Builder => $this
            ->activePosts(Page::query())
            ->where('category_id', $page->category_id)
            ->with('category');

        $previous = $baseQuery()
            ->where(function (Builder $query) use ($page): void {
                $query
                    ->where('created_at', '>', $page->created_at)
                    ->orWhere(function (Builder $query) use ($page): void {
                        $query
                            ->where('created_at', $page->created_at)
                            ->where('id', '>', $page->id);
                    });
            })
            ->orderBy('created_at')
            ->orderBy('id')
            ->first();

        $next = $baseQuery()
            ->where(function (Builder $query) use ($page): void {
                $query
                    ->where('created_at', '<', $page->created_at)
                    ->orWhere(function (Builder $query) use ($page): void {
                        $query
                            ->where('created_at', $page->created_at)
                            ->where('id', '<', $page->id);
                    });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        return new ArticleNeighbors($previous, $next);
    }

    private function activePosts(Builder $query): Builder
    {
        return $query
            ->where('type', PageType::Posts)
            ->where('active', true);
    }
}
