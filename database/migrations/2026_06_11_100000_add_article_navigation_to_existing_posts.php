<?php

declare(strict_types=1);

use App\Enums\PageType;
use App\Models\Page;
use App\Services\ArticleNavigationBlockService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        $service = app(ArticleNavigationBlockService::class);

        Page::query()
            ->withoutGlobalScopes()
            ->where('type', PageType::Posts)
            ->select(['id', 'type'])
            ->chunkById(100, function ($pages) use ($service): void {
                foreach ($pages as $page) {
                    $service->ensureForPage($page);
                }
            });
    }

    public function down(): void
    {
    }
};
