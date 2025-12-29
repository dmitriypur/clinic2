<?php

namespace App\Services;

use App\Models\City;
use Illuminate\Support\Facades\Cache;

class CityService
{
    private ?City $currentCity = null;

    public function setCurrentCity(?City $city): void
    {
        $this->currentCity = $city;
    }

    public function getCurrentCity(): ?City
    {
        return $this->currentCity;
    }

    public function getDefaultCity(): ?City
    {
        return Cache::remember('default_city', 3600, function () {
            return City::where('is_default', true)->where('active', true)->first();
        });
    }

    public function getCityBySlug(string $slug): ?City
    {
        // Cache could be added here
        return City::where('slug', $slug)->where('active', true)->first();
    }

    public function getActiveCities(): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember('active_cities', 3600, function () {
            return City::where('active', true)->get();
        });
    }
}
