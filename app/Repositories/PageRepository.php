<?php

namespace App\Repositories;

use App\Models\Page;
use Illuminate\Support\Facades\Cache;

class PageRepository
{
    private const CACHE_TTL = 2592000;
    
    public function findByHandle(string $handle): ?Page
    {
        $cacheKey = "page-{$handle}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($handle) {
            return Page::query()
                ->byHandle($handle)
                ->active()
                ->withFullRelations()
                ->first();
        });
    }
    
    public function findByCategory(?string $category, ?string $handle): ?Page
    {
        if ($handle === null) {
            return $this->findByHandle($category ?? '/');
        }
        
        return $this->findByHandle($handle);
    }
    
    public function invalidateCache(string $handle): void
    {
        Cache::forget("page-{$handle}");
    }
}