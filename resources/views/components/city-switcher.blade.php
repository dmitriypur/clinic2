@props(['currentCity', 'cities'])

@php
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

    $queryString = request()->getQueryString();
    $query = $queryString ? '?' . $queryString : '';
    
    // Prepare data for Vue component
    $preparedCities = $cities->map(function($city) use ($currentPath, $query, $currentCity) {
        $path = $currentPath ? '/' . $currentPath : '';
        $url = $city->is_default
            ? url($path . $query)
            : url($city->slug . $path . $query);
            
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
