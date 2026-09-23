<?php

namespace App\Services;

use App\Models\City;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class PromotionBlockService
{
    public function __construct(private readonly CityService $cities) {}

    /** @return Collection<int, Promotion> */
    public function forCurrentCity(): Collection
    {
        $slug = $this->cities->getCurrentCity()?->slug ?? 'global';

        // Keep media fresh: its queued conversions can finish after the list is cached.
        $promotions = Cache::remember("active_promotions_{$slug}", 3600, function (): Collection {
            return Promotion::query()
                ->where('archived', false)
                ->get();
        });

        return $promotions->load('media');
    }

    public function forgetAll(): void
    {
        Cache::forget('active_promotions');

        $slugs = City::query()->pluck('slug')->push('global')->unique();

        foreach ($slugs as $slug) {
            Cache::forget("active_promotions_{$slug}");
        }
    }
}
