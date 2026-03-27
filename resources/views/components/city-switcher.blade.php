@props(['currentCity', 'cities'])

@php
    $cityService = app(\App\Services\CityService::class);
    $currentPath = request()->path();
    if ($currentPath === '/') {
        $currentPath = '';
    }

    // Удаляем текущий префикс города из пути, если он есть
    if ($currentCity) {
        $prefix = $currentCity->slug . '/';
        if (str_starts_with($currentPath, $prefix)) {
            $currentPath = substr($currentPath, strlen($prefix));
        } elseif ($currentPath === $currentCity->slug) {
            $currentPath = '';
        }
    }

    $queryParams = request()->query();
    unset($queryParams['force_city']);
    unset($queryParams['test_city']);
    $isGlobalPath = $cityService->isGlobalPath($currentPath);

    // Prepare data for Vue component
    $preparedCities = $cities->map(function($city) use ($currentPath, $queryParams, $currentCity, $isGlobalPath) {
        $path = $currentPath ? '/' . $currentPath : '';
        $forcedCityQuery = array_merge($queryParams, ['force_city' => $city->slug]);
        $url = url($path ?: '/') . '?' . http_build_query($forcedCityQuery);

        return [
            'id' => $city->id,
            'name' => $city->name,
            'slug' => $city->slug,
            'url' => $url,
            'is_current' => $currentCity && $currentCity->id === $city->id
        ];
    })->values();
@endphp

<city-switcher
    :cities='@json($preparedCities)'
    current-city-name="{{ $currentCity ? $currentCity->name : 'Выбрать город' }}"
></city-switcher>
