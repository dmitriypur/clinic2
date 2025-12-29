<?php

namespace App\Services;

use App\Models\Service;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ServicePriceService
{
    public function __construct(
        protected CityService $cityService
    ) {}

    /**
     * Получить дерево услуг с ценами для текущего города
     */
    public function getServicesWithPrices(): Collection
    {
        $city = $this->cityService->getCurrentCity();
        $cityId = $city?->id;
        $slug = $city?->slug ?? 'global';

        // Ключ кэша должен совпадать с тем, что сбрасывается в модели Service
        $cacheKey = "services-with-prices-{$slug}";
        
        // Время жизни кэша - 1 день (или пока не сбросят)
        return Cache::remember($cacheKey, 86400, function () use ($cityId) {
            $services = Service::query()
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->with([
                    'media',
                    'prices',
                    'children' => function ($query) {
                        $query->where('is_active', true)
                            ->orderBy('sort_order')
                            ->with(['prices', 'media']);
                    }
                ])
                ->get();

            // Проходимся по всем услугам и устанавливаем актуальную цену
            foreach ($services as $parent) {
                // Если у родителя тоже могут быть цены (обычно нет, но вдруг)
                $this->setServicePrice($parent, $cityId);

                foreach ($parent->children as $child) {
                    $this->setServicePrice($child, $cityId);
                }
            }

            return $services;
        });
    }

    /**
     * Получить конкретную услугу по UUID с ценами
     */
    public function getServiceByUuid(string $uuid): ?Service
    {
        $city = $this->cityService->getCurrentCity();
        $cityId = $city?->id;

        $service = Service::where('uuid', $uuid)
            ->with([
                'children' => function ($query) {
                    $query->where('is_active', true)
                        ->orderBy('sort_order')
                        ->with('prices');
                }
            ])
            ->first();

        if (!$service) {
            return null;
        }

        // Устанавливаем цены для детей
        foreach ($service->children as $child) {
            $this->setServicePrice($child, $cityId);
        }

        return $service;
    }

    protected function setServicePrice(Service $service, ?int $cityId): void
    {
        // Ищем цену для конкретного города
        $price = $service->prices->first(fn ($p) => $p->city_id === $cityId);

        // Если нет, ищем базовую цену (city_id = null)
        if (!$price) {
            $price = $service->prices->first(fn ($p) => is_null($p->city_id));
        }

        // Устанавливаем поле current_price
        $service->setRelation('current_price', $price);
        $service->current_price = $price;
        
        // Для совместимости, если нужно, можно оставить и prices, 
        // но мы их уже загрузили в relation, так что они доступны через $service->prices
    }
}
