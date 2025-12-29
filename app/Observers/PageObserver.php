<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Page;
use App\Services\PageService;

class PageObserver
{
    public function __construct(
        private readonly PageService $pageService
    ) {}

    /**
     * Handle the Page "created" event.
     */
    public function created(Page $page): void
    {
        $this->clearPageCache($page);
    }

    /**
     * Handle the Page "updated" event.
     */
    public function updated(Page $page): void
    {
        $this->clearPageCache($page);
    }

    /**
     * Handle the Page "deleted" event.
     */
    public function deleted(Page $page): void
    {
        $this->clearPageCache($page);
    }

    /**
     * Handle the Page "restored" event.
     */
    public function restored(Page $page): void
    {
        $this->clearPageCache($page);
    }

    /**
     * Handle the Page "force deleted" event.
     */
    public function forceDeleted(Page $page): void
    {
        $this->clearPageCache($page);
    }

    private function clearPageCache(Page $page): void
    {
        $this->pageService->clearPageCache($page);
    }
}
