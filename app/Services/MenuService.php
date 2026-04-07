<?php

namespace App\Services;

use App\Clinic;
use App\Helpers\Doctors;
use App\Models\Doctor;
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

                if (!$doctor) {
                    $doctorIdOrHandle = (string) $item['data']['id'];
                    $doctor = Doctor::query()
                        ->withoutGlobalScope('city')
                        ->publiclyVisible()
                        ->with('media')
                        ->where(function ($query) use ($doctorIdOrHandle) {
                            $query->where('id', $doctorIdOrHandle)
                                ->orWhere('handle', $doctorIdOrHandle);
                        })
                        ->first();
                }
                
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
                        'receives_display' => $doctor->receives_display,
                        'age_min_months' => $doctor->age_min_months,
                        'age_max_months' => $doctor->age_max_months,
                        'extra' => [
                            'seniority' => $doctor->extra['seniority'] ?? null,
                            'receives_text' => $doctor->receives_text,
                        ]
                    ];
                }
            }
        }

        if (($item['type'] ?? '') === 'file' && !empty($item['data']['file'])) {
            $item['data']['url'] = route('menu-files.download', [
                'encodedPath' => base64_encode($item['data']['file']),
            ]);
            $item['data']['download'] = true;
        }

        // Добавляем префикс города, если он выбран
        if (isset($item['data']['url']) && ($item['type'] ?? '') !== 'file') {
            $url = $item['data']['url'];

            // Проверяем, что это внутренняя ссылка (не начинается с http, mailto, tel, #)
            if (!preg_match('/^(http|https|mailto|tel|#)/', $url)) {
                $item['data']['url'] = ltrim($cityService->addCityPrefix($url), '/');
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
