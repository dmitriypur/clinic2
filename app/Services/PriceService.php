<?php

namespace App\Services;

use App\Clinic;
use App\Models\Service;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PriceService
{
    private const CACHE_TTL = 2592000;

    public function getServicesWithPrices(): Collection
    {
        return Cache::remember('services-with-prices', self::CACHE_TTL, function () {
            $prices = $this->getPrices();

            return Service::query()
                ->with('media')
                ->get()
                ->map(fn($service) => $this->attachPricesToService($service, $prices));
        });
    }

    public function getPrices(): array
    {
        return Cache::remember('clinic-prices', self::CACHE_TTL, function () {
            return Clinic::prices();
        });
    }

    private function attachPricesToService(Service $service, array $prices): Service
    {
        $servicePrices = collect($prices)
            ->firstWhere('uid', $service->uuid);

        $service->prices = $servicePrices['items'] ?? [];

        return $service;
    }

    public function invalidateCache(): void
    {
        Cache::forget('services-with-prices');
        Cache::forget('clinic-prices');
    }
}
