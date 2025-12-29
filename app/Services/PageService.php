<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Page;
use App\Models\Doctor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class PageService
{
    private const CACHE_TTL = 3600; // 1 час

    public function findPageWithBlocks(string $category, ?string $handle = null): ?Page
    {
        $cacheKey = "page_{$category}_" . ($handle ?? 'index');
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($category, $handle) {
            $query = Page::query()
                ->where('active', true)
                ->with([
                    'blocks' => function ($query) {
                        $query->orderBy('order_column');
                    },
                    'blocks.media'
                ]);

            if ($handle) {
                $query->where('handle', $handle);
            } else {
                $query->whereHas('category', function (Builder $q) use ($category) {
                    $q->where('handle', $category);
                });
            }

            return $query->first();
        });
    }

    public function getActiveDoctors(): Collection
    {
        return Cache::remember('active_doctors', self::CACHE_TTL, function () {
            return Doctor::query()
                ->with('media')
                ->get();
        });
    }

    public function getActiveServices(): Collection
    {
        return Cache::remember('active_services', self::CACHE_TTL, function () {
            return Page::query()
                ->where('active', true)
                ->where('type', 'services')
                ->orderBy('sorting')
                ->get(['id', 'title', 'handle']);
        });
    }

    public function clearPageCache(Page $page): void
    {
        $page->loadMissing('category');

        $handlesForIndexKey = array_unique(array_filter([
            $page->handle,
            $page->category?->handle,
        ]));

        $cacheKeys = [
            "page-{$page->handle}",
        ];

        if ($page->category?->handle) {
            $cacheKeys[] = "page-{$page->category->handle}";
            $cacheKeys[] = "page_{$page->category->handle}_{$page->handle}";
        }

        foreach ($handlesForIndexKey as $handleForIndex) {
            $cacheKeys[] = "page_{$handleForIndex}_index";
        }

        foreach (array_unique($cacheKeys) as $cacheKey) {
            Cache::forget($cacheKey);
        }
        
        // Очищаем связанные кеши
        Cache::forget('active_doctors');
        Cache::forget('active_services');

        Cache::flush();
    }

    public function getPageSeoData(Page $page): array
    {
        return [
            'title' => $page->seo['title'] ?? $page->title,
            'description' => $page->seo['description'] ?? null,
            'canonical' => $page->seo['canonical'] ?? null,
            'noindex' => $page->seo['noindex'] ?? false,
        ];
    }

    public function shouldShowPostsView(Page $page): bool
    {
        return in_array($page->type, [\App\Enums\PageType::Posts, \App\Enums\PageType::Blog], true);
    }
}
