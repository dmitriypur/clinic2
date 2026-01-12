<?php

namespace App\Observers;

use App\Models\Doctor;
use App\Services\CityService;
use Illuminate\Support\Facades\Cache;

class DoctorObserver
{
    /**
     * Handle the Doctor "saved" event.
     * Сбрасываем кеш врачей для всех городов при изменении доктора
     */
    public function saved(Doctor $doctor): void
    {
        $this->clearDoctorsCaches();
    }

    /**
     * Handle the Doctor "deleted" event.
     * Сбрасываем кеш врачей для всех городов при удалении доктора
     */
    public function deleted(Doctor $doctor): void
    {
        $this->clearDoctorsCaches();
    }

    /**
     * Очищает кеши врачей для всех активных городов
     * Вызывается при изменении/удалении врача или изменении его привязок к городам
     */
    protected function clearDoctorsCaches(): void
    {
        try {
            $cityService = app(CityService::class);
            $cities = $cityService->getActiveCities();

            // Сбрасываем кеш для каждого города
            foreach ($cities as $city) {
                Cache::forget("doctors-{$city->slug}");
                // Также сбрасываем кеш для страниц с врачами
                Cache::forget("doctors-page-{$city->slug}-1");
            }

            // Сбрасываем глобальный кеш (для админки/API)
            Cache::forget('doctors-global');
            Cache::forget('doctors'); // Старый формат кеша из ScheduleService

        } catch (\Throwable $e) {
            // Логируем ошибку, но не прерываем выполнение
            \Illuminate\Support\Facades\Log::warning('Failed to clear doctors cache', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
