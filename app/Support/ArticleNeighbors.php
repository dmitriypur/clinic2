<?php

namespace App\Support;

use App\Models\Page;

final class ArticleNeighbors
{
    public function __construct(
        public readonly ?Page $previous,
        public readonly ?Page $next,
    ) {}
}
