<?php

namespace App\Services;

use App\Enums\PageType;
use App\Models\Page;
use App\Support\ArticleNeighbors;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class ArticleOrderingService
{
    public function apply(Builder|Relation $query): Builder
    {
        if ($query instanceof Relation) {
            $query = $query->getQuery();
        }

        return $query
            ->where('type', PageType::Posts)
            ->reorder()
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function neighbors(Page $page): ArticleNeighbors
    {
        if ($page->type !== PageType::Posts || ! $page->category_id) {
            return new ArticleNeighbors(null, null);
        }

        $baseQuery = fn (): Builder => Page::query()
            ->where('type', PageType::Posts)
            ->where('active', true)
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
}
