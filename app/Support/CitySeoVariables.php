<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\City;
use App\Services\CityService;

class CitySeoVariables
{
    public function replace(?string $value, ?City $city = null): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return strtr($value, $this->variables($city));
    }

    public function variables(?City $city = null): array
    {
        $city ??= $this->resolveCity();

        if (!$city) {
            return [];
        }

        $seoCases = (array) ($city->seo_cases ?? []);

        return [
            '{city}' => $city->name,
            '{city_prepositional}' => $this->value($seoCases, 'prepositional', $city->name),
            '{city_genitive}' => $this->value($seoCases, 'genitive', $city->name),
            '{city_accusative}' => $this->value($seoCases, 'accusative', $city->name),
            '{city_phone}' => (string) ($city->phone ?? ''),
        ];
    }

    public static function placeholders(): array
    {
        return [
            '{city}',
            '{city_prepositional}',
            '{city_genitive}',
            '{city_accusative}',
            '{city_phone}',
        ];
    }

    private function resolveCity(): ?City
    {
        /** @var CityService $cityService */
        $cityService = app(CityService::class);

        return $cityService->getCurrentCity() ?? $cityService->getDefaultCity();
    }

    private function value(array $seoCases, string $key, string $fallback): string
    {
        $value = trim((string) ($seoCases[$key] ?? ''));

        return $value !== '' ? $value : $fallback;
    }
}
