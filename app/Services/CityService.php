<?php

namespace App\Services;

use App\Models\City;
use Illuminate\Support\Facades\Cache;

class CityService
{
    private const GLOBAL_PATH_PREFIXES = [
        'stati',
        'directory',
        'tags',
        'search',
        'live-search',
    ];

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
        return Cache::remember("city_by_slug_{$slug}", 3600, function () use ($slug) {
            return City::where('slug', $slug)->where('active', true)->first();
        });
    }

    public function getActiveCities(): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember('active_cities', 3600, function () {
            return City::where('active', true)->get();
        });
    }

    /**
     * Добавляет префикс текущего города к пути (если город не дефолтный)
     * Централизованный метод для избежания дублирования логики
     *
     * @param string $path Путь без префикса (например, '/services' или 'doctors/ivanov')
     * @return string Путь с префиксом города (например, '/spb/services')
     */
    public function addCityPrefix(string $path): string
    {
        $normalizedPath = '/' . ltrim($path, '/');

        if ($this->isGlobalPath($normalizedPath)) {
            return $normalizedPath === '//' ? '/' : $normalizedPath;
        }

        $city = $this->getCurrentCity();

        // Если город не выбран или является дефолтным - возвращаем путь как есть
        if (!$city || $city->is_default) {
            return $normalizedPath;
        }

        $cleanPath = ltrim($normalizedPath, '/');
        $slug = $city->slug;

        // Защита от дублирования префикса
        if (empty($cleanPath)) {
            return '/' . $slug;
        }

        if (str_starts_with($cleanPath, $slug . '/')) {
            return '/' . $cleanPath;
        }

        return '/' . $slug . '/' . $cleanPath;
    }

    public function isGlobalPath(string $path): bool
    {
        $cleanPath = trim($path, '/');

        if ($cleanPath === '') {
            return false;
        }

        foreach (self::GLOBAL_PATH_PREFIXES as $prefix) {
            if ($cleanPath === $prefix || str_starts_with($cleanPath, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }
}
