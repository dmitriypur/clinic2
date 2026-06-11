<?php

namespace App\Support;

use App\Models\Page;

final readonly class ArticleNeighbors
{
    public function __construct(
        public ?Page $previous,
        public ?Page $next,
    ) {}
}
