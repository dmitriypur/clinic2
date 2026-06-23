<?php

namespace App\Services;

use App\Contracts\ArticleSortStrategy;
use App\Models\Page;
use App\Support\ArticleNeighbors;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class ArticleOrderingService
{
    public function __construct(
        private readonly ArticleSortStrategy $strategy,
    ) {}

    public function apply(Builder|Relation $query): Builder
    {
        if ($query instanceof Relation) {
            $query = $query->getQuery();
        }

        return $this->strategy->apply($query);
    }

    public function neighbors(Page $page): ArticleNeighbors
    {
        return $this->strategy->neighbors($page);
    }
}
