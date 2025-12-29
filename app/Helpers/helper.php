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

        // 2. Получаем текущий город
        $cityService = app(\App\Services\CityService::class);
        $city = $cityService->getCurrentCity();

        // 3. Если город есть и он не дефолтный — добавляем префикс
        if ($city && !$city->is_default) {
            // Получится /spb/doctors/ivanov
            $path = '/' . $city->slug . '/' . ltrim($path, '/');
        }

        // 4. Если нужен абсолютный URL (http://site.ru/...), оборачиваем в url()
        if ($absolute) {
            return url($path);
        }

        return $path;
    }
}

if (!function_exists('home_route')) {
    function home_route()
    {
        $cityService = app(\App\Services\CityService::class);
        $city = $cityService->getCurrentCity();

        if ($city && !$city->is_default) {
            return url($city->slug);
        }

        return url('/');
    }
}
