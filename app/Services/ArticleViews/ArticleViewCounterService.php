<?php

namespace App\Services\ArticleViews;

use App\Models\ArticleViewCounter;
use App\Models\Page;

class ArticleViewCounterService
{
    public function incrementForPage(Page $page, ?string $pagePath = null): ArticleViewCounter
    {
        $pagePath = $this->normalizeArticlePath($pagePath) ?? $this->pathForPage($page);

        $counter = ArticleViewCounter::query()->firstOrCreate(
            ['page_path_hash' => $this->hashPath($pagePath)],
            [
                'page_path' => $pagePath,
                'handle' => $page->handle,
                'page_id' => $page->id,
                'views_count' => 0,
                'source' => 'local',
            ],
        );

        $counter->forceFill([
            'handle' => $page->handle,
            'page_id' => $page->id,
        ])->save();

        $counter->increment('views_count');

        return $counter->refresh();
    }

    public function pathForPage(Page $page): string
    {
        $categoryHandle = $page->relationLoaded('category')
            ? $page->category?->handle
            : $page->category()->value('handle');

        return '/' . trim(($categoryHandle ?: 'stati') . '/' . $page->handle, '/');
    }

    public function normalizeArticlePath(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);
        $path = is_string($path) ? $path : $url;
        $path = '/' . trim($path, '/');

        if (! preg_match('~^/stati/([^/]+)$~u', $path, $matches)) {
            return null;
        }

        return '/stati/' . $matches[1];
    }

    public function handleFromPath(string $pagePath): ?string
    {
        return preg_match('~^/stati/([^/]+)$~u', $pagePath, $matches) ? $matches[1] : null;
    }

    public function hashPath(string $pagePath): string
    {
        return hash('sha256', $pagePath);
    }
}
