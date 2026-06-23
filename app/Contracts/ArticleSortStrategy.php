<?php

namespace App\Contracts;

use App\Models\Page;
use App\Support\ArticleNeighbors;
use Illuminate\Database\Eloquent\Builder;

interface ArticleSortStrategy
{
    public function apply(Builder $query): Builder;

    public function neighbors(Page $page): ArticleNeighbors;
}
