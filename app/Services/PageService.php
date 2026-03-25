<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PageType;
use App\Models\Category;
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
                ->publiclyVisible()
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
        $categoryHandles = $this->resolveRelevantCategoryHandles($page);
        $pageHandles = $this->resolveRelevantPageHandles($page, $categoryHandles);
        $citySlugs = $this->resolveRelevantCitySlugs();

        foreach ($this->buildPageCacheKeys($citySlugs, $pageHandles, $categoryHandles) as $cacheKey) {
            Cache::forget($cacheKey);
        }

        foreach ($this->buildLegacyPageCacheKeys($pageHandles, $categoryHandles) as $cacheKey) {
            Cache::forget($cacheKey);
        }

        $this->clearRelatedListingCaches($page);
    }

    public function getPageSeoData(Page $page): array
    {
        $page = $page->withResolvedCitySeoVariables();

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

    private function resolveRelevantCategoryHandles(Page $page): array
    {
        $handles = array_filter([
            $page->category?->handle,
        ]);

        $originalCategoryId = $page->getOriginal('category_id');
        if ($originalCategoryId && (int) $originalCategoryId !== (int) $page->category_id) {
            $originalCategory = Category::query()->find($originalCategoryId);
            if ($originalCategory?->handle) {
                $handles[] = $originalCategory->handle;
            }
        }

        return array_values(array_unique($handles));
    }

    private function resolveRelevantPageHandles(Page $page, array $categoryHandles): array
    {
        return array_values(array_unique(array_filter([
            $page->handle,
            $page->getOriginal('handle'),
            ...$categoryHandles,
        ])));
    }

    private function resolveRelevantCitySlugs(): array
    {
        return app(CityService::class)
            ->getActiveCities()
            ->pluck('slug')
            ->push('global')
            ->unique()
            ->values()
            ->all();
    }

    private function buildPageCacheKeys(array $citySlugs, array $pageHandles, array $categoryHandles): array
    {
        $cacheKeys = [];

        foreach ($citySlugs as $slug) {
            foreach ($pageHandles as $handle) {
                $cacheKeys[] = "page-{$slug}-{$handle}";
            }

            foreach ($pageHandles as $handle) {
                foreach ($categoryHandles as $categoryHandle) {
                    $cacheKeys[] = "page-{$slug}-{$categoryHandle}/{$handle}";
                }
            }
        }

        return array_values(array_unique($cacheKeys));
    }

    private function buildLegacyPageCacheKeys(array $pageHandles, array $categoryHandles): array
    {
        $cacheKeys = [];

        foreach ($pageHandles as $handle) {
            $cacheKeys[] = "page-{$handle}";
        }

        foreach ($pageHandles as $handleForIndex) {
            $cacheKeys[] = "page_{$handleForIndex}_index";
        }

        foreach ($categoryHandles as $categoryHandle) {
            foreach ($pageHandles as $pageHandle) {
                $cacheKeys[] = "page_{$categoryHandle}_{$pageHandle}";
            }
        }

        return array_values(array_unique($cacheKeys));
    }

    private function clearRelatedListingCaches(Page $page): void
    {
        Cache::forget('active_doctors');
        Cache::forget('active_services');

        if ($page->type === PageType::Doctors) {
            foreach ($this->resolveRelevantCitySlugs() as $slug) {
                for ($pageNumber = 1; $pageNumber <= 20; $pageNumber++) {
                    Cache::forget("doctors-page-{$slug}-{$pageNumber}");
                }
            }
        }

        if (in_array($page->type, [PageType::Posts, PageType::Blog], true)) {
            Cache::forget('posts_filter');
            Cache::forget('blog_posts_for_slider');
        }
    }
}
