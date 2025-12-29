<?php

namespace App\Services;

use App\Clinic;
use App\Helpers\Doctors;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Request;

class MenuService
{
    public function prepareItems(array $items): array
    {
        return $this->mapItems(collect($items))->toArray();
    }

    protected function mapItems(Collection $items): Collection
    {
        return $items->map(function ($item) {
            return $this->mapItemWithChildren($item);
        })->filter()->values();
    }

    protected function mapItemWithChildren(array $item): ?array
    {
        $cityService = app(\App\Services\CityService::class);
        $currentCity = $cityService->getCurrentCity();

        // Проверка привязки к городам
        $assignedCities = $item['data']['cities'] ?? [];
        if (!empty($assignedCities) && $currentCity) {
            if (!in_array($currentCity->id, $assignedCities)) {
                return null;
            }
        }

        $item['is_doctor_grid'] = false;

        // Обработка типа "Врач"
        if (($item['type'] ?? '') === 'doctor') {
            $item['data']['url'] = 'doctors/' . ($item['data']['id'] ?? '');
            
            if (isset($item['data']['id'])) {
                $doctors = Doctors::getDoctors();
                $doctor = $doctors->firstWhere('id', $item['data']['id']);
                
                if ($doctor) {
                    $item['data']['doctor'] = [
                        'id' => $doctor->id,
                        'name' => $doctor->name,
                        'surname' => $doctor->surname,
                        'speciality' => $doctor->speciality,
                        'position' => $doctor->position,
                        'avatar' => $doctor->getFirstMediaUrl(),
                        'video_url' => $doctor->actual_video_url,
                        'url' => route('doctor.show', $doctor->id),
                        'extra' => [
                            'seniority' => $doctor->extra['seniority'] ?? null,
                            'receives' => $doctor->extra['receives'] ?? null
                        ]
                    ];
                }
            }
        }

        // Добавляем префикс города, если он выбран
        if (isset($item['data']['url'])) {
            $url = $item['data']['url'];

            // Проверяем, что это внутренняя ссылка (не начинается с http, mailto, tel, #)
            if (!preg_match('/^(http|https|mailto|tel|#)/', $url)) {
                // Добавляем префикс только если город выбран и он не дефолтный
                if ($currentCity && !$currentCity->is_default) {
                    $slug = $currentCity->slug;
                    // Убираем начальный слеш для чистого соединения
                    $cleanUrl = ltrim($url, '/');

                    // Если ссылка пустая (главная страница)
                    if (empty($cleanUrl)) {
                        $item['data']['url'] = $slug;
                    } 
                    // Если ссылка не начинается уже с этого слага (защита от дублирования)
                    elseif (!str_starts_with($cleanUrl, $slug . '/')) {
                        $item['data']['url'] = $slug . '/' . $cleanUrl;
                    }
                }
            }
        }

        // Обновляем URL
        if (isset($item['data']['url'])) {
            $item['data']['url'] = Clinic::relativeUrl(url($item['data']['url']));
        }

        // Нормализация изображения (если это массив, берем первый элемент)
        if (isset($item['data']['image']) && is_array($item['data']['image'])) {
            $item['data']['image'] = array_values($item['data']['image'])[0] ?? null;
        }

        // Рекурсивная обработка детей
        $children = collect($item['children'] ?? [])
            ->map(fn ($child) => $this->mapItemWithChildren($child))
            ->filter()
            ->values();

        $item['children'] = $children->toArray();

        // Автоматическое определение: если есть дети-врачи, значит это сетка врачей
        if ($children->contains(fn($child) => ($child['type'] ?? '') === 'doctor')) {
            $item['is_doctor_grid'] = true;
        }

        // Проверяем, есть ли картинки у дочерних элементов
        $hasImages = $children->contains(fn($child) => !empty($child['data']['image']));

        // Определяем тип меню:
        // Если это сетка врачей ИЛИ есть картинки -> Mega Menu (оставляем как есть, is_simple = false)
        // Иначе -> Simple Menu (обычный выпадающий список)
        $item['is_simple'] = !$item['is_doctor_grid'] && !$hasImages;

        // Определение активности
        $currentUrl = Request::url();
        $itemUrl = isset($item['data']['url']) ? url($item['data']['url']) : '';
        
        $selfActive = $currentUrl === $itemUrl;
        $childrenActive = $children->contains(fn($child) => $child['active'] ?? false);

        $item['active'] = $selfActive || $childrenActive;

        return $item;
    }
}
