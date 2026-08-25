<?php

namespace App\Services;

class MobileNavigationService
{
    private const SLOT_BY_PATH = [
        'services' => 'services',
        'doctors' => 'doctors',
        'uslugi-i-ceny' => 'prices',
        'kontakty' => 'contacts',
    ];

    private const MOBILE_LABEL_BY_PATH = [
        'doctors' => 'Врачи',
        'o-klinike' => 'О клинике',
    ];

    private const BOTTOM_ORDER = [
        'services',
        'doctors',
        'prices',
        'contacts',
    ];

    public function markItems(
        array $items,
        ?string $currentCitySlug = null,
        ?string $currentHost = null,
    ): array
    {
        return array_map(function (array $item) use ($currentCitySlug, $currentHost): array {
            $path = $this->canonicalPath($item['data']['url'] ?? null, $currentCitySlug, $currentHost);

            if ($path !== null && isset(self::SLOT_BY_PATH[$path])) {
                $item['mobile_navigation_slot'] = self::SLOT_BY_PATH[$path];
            }

            if ($path !== null && isset(self::MOBILE_LABEL_BY_PATH[$path])) {
                $item['mobile_label'] = self::MOBILE_LABEL_BY_PATH[$path];
            }

            return $item;
        }, array_values($items));
    }

    public function bottomItems(array $items): array
    {
        $itemsBySlot = [];

        foreach ($items as $item) {
            $slot = $item['mobile_navigation_slot'] ?? null;

            if ($slot && ! isset($itemsBySlot[$slot])) {
                $itemsBySlot[$slot] = $item;
            }
        }

        $orderedItems = [];

        foreach (self::BOTTOM_ORDER as $slot) {
            if (isset($itemsBySlot[$slot])) {
                $orderedItems[$slot] = $itemsBySlot[$slot];
            }
        }

        return $orderedItems;
    }

    private function canonicalPath(
        ?string $url,
        ?string $currentCitySlug,
        ?string $currentHost,
    ): ?string
    {
        if (! $url) {
            return null;
        }

        $urlHost = parse_url($url, PHP_URL_HOST);

        if ($urlHost && (! $currentHost || strcasecmp($urlHost, $currentHost) !== 0)) {
            return null;
        }

        if (parse_url($url, PHP_URL_SCHEME) && ! $urlHost) {
            return null;
        }

        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');

        if ($currentCitySlug && str_starts_with($path, $currentCitySlug . '/')) {
            $path = substr($path, strlen($currentCitySlug) + 1);
        }

        return $path;
    }
}
