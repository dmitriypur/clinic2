<?php

namespace App\View\Components;

use App\Models\Page;
use App\Services\ArticleOrderingService;
use App\Support\ArticleNeighbors;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ArticleNavigation extends Component
{
    public ArticleNeighbors $neighbors;

    public function __construct(
        public Page $page,
        ArticleOrderingService $articleOrderingService,
    ) {
        $this->neighbors = $articleOrderingService->neighbors($page);
    }

    public function render(): View|Closure|string
    {
        return view('components.block.article-navigation');
    }
}
