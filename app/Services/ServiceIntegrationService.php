<?php

namespace App\Services;

use App\Exceptions\ServiceIntegrationException;
use App\Models\City;
use App\Models\Service;
use App\Models\ServicePrice;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ServiceIntegrationService
{
    protected array $refs = [];

    public function getTree(bool $includeInactive = true): array
    {
        $query = Service::query()
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->with([
                'cities:id,slug,name',
                'prices.city:id,slug,name',
                'children' => function ($query) use ($includeInactive) {
                    if (! $includeInactive) {
                        $query->where('is_active', true);
                    }

                    $query->orderBy('sort_order')
                        ->with([
                            'cities:id,slug,name',
                            'prices.city:id,slug,name',
                        ]);
                },
            ]);

        if (! $includeInactive) {
            $query->where('is_active', true);
        }

        return [
            'services' => $query
                ->get()
                ->map(fn (Service $service) => $this->serializeService($service, true))
                ->values()
                ->all(),
            'cities' => $this->serializeCities(),
        ];
    }

    public function search(string $query, int $limit = 20): array
    {
        $services = Service::query()
            ->with('parent:id,uuid,title')
            ->where(function ($builder) use ($query) {
                $builder->where('title', 'like', "%{$query}%")
                    ->orWhere('uuid', 'like', "%{$query}%");
            })
            ->orderByRaw('parent_id is null desc')
            ->orderBy('sort_order')
            ->limit($limit)
            ->get();

        return $services->map(fn (Service $service) => [
            'uuid' => $service->uuid,
            'title' => $service->title,
            'is_active' => (bool) $service->is_active,
            'sort_order' => (int) $service->sort_order,
            'level' => $service->parent_id === null ? 'parent' : 'child',
            'parent_uuid' => $service->parent?->uuid,
            'parent_title' => $service->parent?->title,
        ])->values()->all();
    }

    public function getService(string $uuid): array
    {
        $service = Service::query()
            ->with([
                'parent:id,uuid,title',
                'cities:id,slug,name',
                'prices.city:id,slug,name',
                'children' => function ($query) {
                    $query->orderBy('sort_order')
                        ->with([
                            'cities:id,slug,name',
                            'prices.city:id,slug,name',
                        ]);
                },
            ])
            ->where('uuid', $uuid)
            ->first();

        if (! $service) {
            throw (new ModelNotFoundException)->setModel(Service::class, [$uuid]);
        }

        return $this->serializeService($service, true);
    }

    public function applyOperations(array $operations, bool $dryRun = false): array
    {
        $this->refs = [];
        $runner = fn () => $this->runOperations($operations);

        if ($dryRun) {
            DB::beginTransaction();

            try {
                $results = Service::withoutEvents(
                    fn () => ServicePrice::withoutEvents($runner)
                );

                DB::rollBack();
            } catch (\Throwable $exception) {
                DB::rollBack();
                throw $exception;
            }
        } else {
            DB::beginTransaction();

            try {
                $results = $runner();
                DB::commit();
            } catch (\Throwable $exception) {
                DB::rollBack();
                throw $exception;
            }
        }

        return [
            'dry_run' => $dryRun,
            'results' => $results,
            'refs' => $this->refs,
        ];
    }

    protected function runOperations(array $operations): array
    {
        $results = [];

        foreach ($operations as $index => $operation) {
            $results[] = $this->runOperation($operation, $index);
        }

        return $results;
    }

    protected function runOperation(array $operation, int $index): array
    {
        return match ($operation['type']) {
            'create_service' => $this->createService($operation, $index),
            'update_service' => $this->updateService($operation, $index),
            'delete_service' => $this->deleteService($operation, $index),
            'upsert_price' => $this->upsertPrice($operation, $index),
            'delete_price' => $this->deletePrice($operation, $index),
            default => throw new ServiceIntegrationException(
                "Неподдерживаемый тип операции [{$operation['type']}].",
                422,
                ['operation_index' => $index]
            ),
        };
    }

    protected function createService(array $operation, int $index): array
    {
        $parent = $this->resolveParent($operation, $index);

        if ($parent && $parent->parent_id !== null) {
            throw new ServiceIntegrationException(
                'Вложенность подуслуг глубже одного уровня не поддерживается.',
                422,
                ['operation_index' => $index, 'parent_uuid' => $parent->uuid]
            );
        }

        $service = Service::create([
            'title' => $operation['title'],
            'uuid' => Arr::get($operation, 'uuid', (string) Str::uuid()),
            'parent_id' => $parent?->id,
            'sort_order' => (int) Arr::get($operation, 'sort_order', 0),
            'is_active' => (bool) Arr::get($operation, 'is_active', true),
        ]);

        if (array_key_exists('city_slugs', $operation)) {
            $this->syncCities($service, Arr::get($operation, 'city_slugs', []), $index);
            $service->load('cities:id,slug,name');
        }

        if (filled($operation['ref'] ?? null)) {
            $this->refs[$operation['ref']] = $service->uuid;
        }

        return [
            'operation_index' => $index,
            'type' => 'create_service',
            'status' => 'created',
            'service' => $this->freshSerializedService($service->uuid),
        ];
    }

    protected function updateService(array $operation, int $index): array
    {
        $service = $this->resolveService($operation, $index);

        $attributes = [];

        if (array_key_exists('title', $operation)) {
            $attributes['title'] = $operation['title'];
        }

        if (array_key_exists('sort_order', $operation)) {
            $attributes['sort_order'] = (int) $operation['sort_order'];
        }

        if (array_key_exists('is_active', $operation)) {
            $attributes['is_active'] = (bool) $operation['is_active'];
        }

        if (array_key_exists('parent_uuid', $operation) || array_key_exists('parent_ref', $operation)) {
            $parent = $this->resolveParent($operation, $index);

            if ($parent && $parent->uuid === $service->uuid) {
                throw new ServiceIntegrationException(
                    'Услуга не может быть родителем самой себе.',
                    422,
                    ['operation_index' => $index, 'service_uuid' => $service->uuid]
                );
            }

            if ($parent && $parent->parent_id !== null) {
                throw new ServiceIntegrationException(
                    'Вложенность подуслуг глубже одного уровня не поддерживается.',
                    422,
                    ['operation_index' => $index, 'parent_uuid' => $parent->uuid]
                );
            }

            if ($parent && $service->children()->exists()) {
                throw new ServiceIntegrationException(
                    'Родительскую услугу с подуслугами нельзя перенести внутрь другой услуги.',
                    422,
                    ['operation_index' => $index, 'service_uuid' => $service->uuid]
                );
            }

            $attributes['parent_id'] = $parent?->id;
        }

        if ($attributes !== []) {
            $service->fill($attributes)->save();
        }

        if (array_key_exists('city_slugs', $operation)) {
            $this->syncCities($service, Arr::get($operation, 'city_slugs', []), $index);
        }

        return [
            'operation_index' => $index,
            'type' => 'update_service',
            'status' => 'updated',
            'service' => $this->freshSerializedService($service->uuid),
        ];
    }

    protected function deleteService(array $operation, int $index): array
    {
        $service = $this->resolveService($operation, $index);
        $serviceData = $this->freshSerializedService($service->uuid);
        $childUuids = $service->children()->pluck('uuid')->all();

        if ($childUuids !== [] && ! Arr::get($operation, 'cascade_children', false)) {
            throw new ServiceIntegrationException(
                'У этой услуги есть подуслуги. Передайте cascade_children=true, чтобы удалить её вместе с ними.',
                422,
                ['operation_index' => $index, 'service_uuid' => $service->uuid]
            );
        }

        foreach ($service->children()->get() as $child) {
            $child->delete();
        }

        $service->delete();

        return [
            'operation_index' => $index,
            'type' => 'delete_service',
            'status' => 'deleted',
            'service' => $serviceData,
            'deleted_child_uuids' => $childUuids,
        ];
    }

    protected function upsertPrice(array $operation, int $index): array
    {
        $service = $this->resolveService($operation, $index);
        $city = $this->resolveCity(Arr::get($operation, 'city_slug'), $index);

        $price = ServicePrice::query()
            ->where('service_id', $service->id)
            ->where(function ($query) use ($city) {
                if ($city) {
                    $query->where('city_id', $city->id);
                } else {
                    $query->whereNull('city_id');
                }
            })
            ->first();

        if (! $price) {
            $price = new ServicePrice([
                'service_id' => $service->id,
                'city_id' => $city?->id,
            ]);
        }

        $price->fill([
            'price' => (int) $operation['price'],
            'old_price' => Arr::get($operation, 'old_price'),
            'price_from' => (bool) Arr::get($operation, 'price_from', false),
        ]);
        $price->save();

        return [
            'operation_index' => $index,
            'type' => 'upsert_price',
            'status' => $price->wasRecentlyCreated ? 'created' : 'updated',
            'service' => $this->freshSerializedService($service->uuid),
            'price' => $this->serializePrice($price->fresh(['city:id,slug,name'])),
        ];
    }

    protected function deletePrice(array $operation, int $index): array
    {
        $service = $this->resolveService($operation, $index);
        $city = $this->resolveCity(Arr::get($operation, 'city_slug'), $index);

        $price = ServicePrice::query()
            ->where('service_id', $service->id)
            ->where(function ($query) use ($city) {
                if ($city) {
                    $query->where('city_id', $city->id);
                } else {
                    $query->whereNull('city_id');
                }
            })
            ->first();

        if (! $price) {
            throw new ServiceIntegrationException(
                'Цена для этой услуги и города не найдена.',
                404,
                ['operation_index' => $index, 'service_uuid' => $service->uuid, 'city_slug' => $city?->slug]
            );
        }

        $priceData = $this->serializePrice($price->load('city:id,slug,name'));
        $price->delete();

        return [
            'operation_index' => $index,
            'type' => 'delete_price',
            'status' => 'deleted',
            'service_uuid' => $service->uuid,
            'price' => $priceData,
        ];
    }

    protected function resolveService(array $operation, int $index): Service
    {
        $uuid = Arr::get($operation, 'service_uuid');
        $ref = Arr::get($operation, 'service_ref');

        if (filled($ref)) {
            $uuid = $this->refs[$ref] ?? null;

            if (! $uuid) {
                throw new ServiceIntegrationException(
                    "Неизвестный service_ref [{$ref}].",
                    422,
                    ['operation_index' => $index, 'service_ref' => $ref]
                );
            }
        }

        $service = Service::query()->where('uuid', $uuid)->first();

        if (! $service) {
            throw new ServiceIntegrationException(
                'Услуга не найдена.',
                404,
                ['operation_index' => $index, 'service_uuid' => $uuid]
            );
        }

        return $service;
    }

    protected function resolveParent(array $operation, int $index): ?Service
    {
        if (array_key_exists('parent_uuid', $operation) && $operation['parent_uuid'] === null) {
            return null;
        }

        if (array_key_exists('parent_ref', $operation) && $operation['parent_ref'] === null) {
            return null;
        }

        if (! filled($operation['parent_uuid'] ?? null) && ! filled($operation['parent_ref'] ?? null)) {
            return null;
        }

        return $this->resolveService([
            'service_uuid' => Arr::get($operation, 'parent_uuid'),
            'service_ref' => Arr::get($operation, 'parent_ref'),
        ], $index);
    }

    protected function resolveCity(?string $slug, int $index): ?City
    {
        if ($slug === null || $slug === '') {
            return null;
        }

        $city = City::query()->where('slug', $slug)->first();

        if (! $city) {
            throw new ServiceIntegrationException(
                "Город [{$slug}] не найден.",
                404,
                ['operation_index' => $index, 'city_slug' => $slug]
            );
        }

        return $city;
    }

    protected function syncCities(Service $service, array $citySlugs, int $index): void
    {
        $cities = City::query()
            ->whereIn('slug', $citySlugs)
            ->get(['id', 'slug']);

        if ($cities->count() !== count(array_unique($citySlugs))) {
            $foundSlugs = $cities->pluck('slug')->all();
            $missingSlugs = array_values(array_diff(array_unique($citySlugs), $foundSlugs));

            throw new ServiceIntegrationException(
                'Один или несколько городов не найдены.',
                422,
                ['operation_index' => $index, 'missing_city_slugs' => $missingSlugs]
            );
        }

        $service->cities()->sync($cities->pluck('id')->all());
    }

    protected function freshSerializedService(string $uuid): array
    {
        return $this->serializeService(
            Service::query()
                ->with([
                    'parent:id,uuid,title',
                    'cities:id,slug,name',
                    'prices.city:id,slug,name',
                    'children' => function ($query) {
                        $query->orderBy('sort_order')
                            ->with([
                                'cities:id,slug,name',
                                'prices.city:id,slug,name',
                            ]);
                    },
                ])
                ->where('uuid', $uuid)
                ->firstOrFail(),
            true
        );
    }

    protected function serializeService(Service $service, bool $includeChildren = false): array
    {
        return [
            'uuid' => $service->uuid,
            'title' => $service->title,
            'parent_uuid' => $service->relationLoaded('parent') ? $service->parent?->uuid : null,
            'is_active' => (bool) $service->is_active,
            'sort_order' => (int) $service->sort_order,
            'city_slugs' => $service->cities->pluck('slug')->values()->all(),
            'available_in_all_cities' => $service->cities->isEmpty(),
            'prices' => $service->prices
                ->sortBy(fn (ServicePrice $price) => $price->city?->slug ?? '')
                ->map(fn (ServicePrice $price) => $this->serializePrice($price))
                ->values()
                ->all(),
            'children' => $includeChildren
                ? $service->children
                    ->sortBy('sort_order')
                    ->map(fn (Service $child) => $this->serializeService($child))
                    ->values()
                    ->all()
                : [],
        ];
    }

    protected function serializePrice(ServicePrice $price): array
    {
        return [
            'city_slug' => $price->city?->slug,
            'city_name' => $price->city?->name,
            'price' => (int) $price->price,
            'old_price' => $price->old_price === null ? null : (int) $price->old_price,
            'price_from' => (bool) $price->price_from,
        ];
    }

    protected function serializeCities(): array
    {
        return City::query()
            ->orderBy('name')
            ->get(['id', 'slug', 'name'])
            ->map(fn (City $city) => [
                'slug' => $city->slug,
                'name' => $city->name,
            ])
            ->values()
            ->all();
    }
}
