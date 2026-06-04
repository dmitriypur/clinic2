<?php

use App\Settings\GeneralSettings;

function reduction($text, $length = 70) {
    if (mb_strlen($text ?? '', 'UTF-8') > $length) {
        $substr = mb_substr($text, 0, $length, 'UTF-8');

        $text = strpos($substr, ' ') !== false
            ? preg_replace('~(\s)?(?(1)\S+$|\s$)~', '', $substr)
            : strstr($text, ' ', true);

        $text .= ' ... ';
    }

    return $text;
}

function getSettings(){
    $settings = app(GeneralSettings::class);
    return $settings;
}

if (!function_exists('article_booking_count')) {
    function article_booking_count(\App\Models\Page|int|null $page, ?int $cityId = null): int
    {
        if (!$page) {
            return 0;
        }

        $pageId = $page instanceof \App\Models\Page ? $page->id : $page;
        $cityId ??= app(\App\Services\CityService::class)->getCurrentCity()?->id;

        return \App\Models\ArticleBookingConversion::query()
            ->where('page_id', $pageId)
            ->when($cityId, fn($query) => $query->where('city_id', $cityId))
            ->count();
    }
}

if (!function_exists('article_views_count')) {
    function article_views_count(\App\Models\Page|int|null $page): int
    {
        if (!$page) {
            return 0;
        }

        if ($page instanceof \App\Models\Page) {
            if (isset($page->article_views_count)) {
                return (int) $page->article_views_count;
            }

            if ($page->relationLoaded('articleViewCounter')) {
                return (int) ($page->articleViewCounter?->views_count ?? 0);
            }

            $page = $page->id;
        }

        return (int) \App\Models\ArticleViewCounter::query()
            ->where('page_id', $page)
            ->value('views_count');
    }
}


/**
 * Находит путь к активному пункту меню (2-го или 3-го уровня) и возвращает его индексы и изображение.
 * Если ни один пункт не активен, возвращает данные первого пункта 2-го уровня (по умолчанию).
 *
 * @param array $items Массив пунктов меню (children)
 * @return array [
 *   'parent' => индекс активного пункта 2-го уровня или 0 (если ничего не активно),
 *   'child' => индекс активного пункта 3-го уровня или null,
 *   'image' => картинка активного пункта или первого пункта 2-го уровня
 * ]
 */
function findActivePath(array $items): array
{
    foreach ($items as $parentIndex => $item) {
        // Проверяем, активен ли пункт 2-го уровня
        if (!empty($item['active'])) {
            // Если есть дети (3-й уровень), ищем активного среди них
            if (!empty($item['children']) && is_array($item['children'])) {
                foreach ($item['children'] as $childIndex => $child) {
                    if (!empty($child['active'])) {
                        // Активен пункт 3-го уровня — возвращаем его индексы и картинку
                        return [
                            'parent' => $parentIndex,
                            'child' => $childIndex,
                            'image' => $child['data']['image'] ?? ($item['data']['image'] ?? ''),
                        ];
                    }
                }
            }
            // Активен пункт 2-го уровня (или нет активных детей)
            return [
                'parent' => $parentIndex,
                'child' => null,
                'image' => $item['data']['image'] ?? '',
            ];
        }
    }
    // Нет активных — возвращаем первый пункт 2-го уровня (по умолчанию)
    if (!empty($items[0])) {
        return [
            'parent' => 0,
            'child' => null,
            'image' => $items[0]['data']['image'] ?? '',
        ];
    }
    // Если массив пустой — возвращаем пустые значения
    return [
        'parent' => null,
        'child' => null,
        'image' => '',
    ];
}

if (!function_exists('city_route')) {
    function city_route($name, $parameters = [], $absolute = true)
    {
        // 1. Генерируем базовый относительный путь (например, /doctors/ivanov)
        $path = route($name, $parameters, false);

        // 2. Используем централизованный метод для добавления префикса города
        $cityService = app(\App\Services\CityService::class);
        $path = $cityService->addCityPrefix($path);

        // 3. Если нужен абсолютный URL (http://site.ru/...), оборачиваем в url()
        if ($absolute) {
            return url($path);
        }

        return $path;
    }
}

if (!function_exists('city_url')) {
    function city_url(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (preg_match('~^(?:[a-z][a-z0-9+.-]*:)?//~i', $path) || str_starts_with($path, '#') || str_starts_with($path, 'mailto:') || str_starts_with($path, 'tel:')) {
            return $path;
        }

        $cityService = app(\App\Services\CityService::class);

        return url($cityService->addCityPrefix($path));
    }
}

if (!function_exists('home_route')) {
    function home_route()
    {
        $cityService = app(\App\Services\CityService::class);
        $path = $cityService->addCityPrefix('/');

        return url($path);
    }
}
