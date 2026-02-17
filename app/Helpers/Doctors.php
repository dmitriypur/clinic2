<?php
namespace App\Helpers;
use App\Models\Doctor;
use Illuminate\Support\Facades\Cache;

class Doctors
{
    public static function getDoctors()
    {
        $cityService = app(\App\Services\CityService::class);
        $currentCity = $cityService->getCurrentCity();
        $slug = $currentCity ? $currentCity->slug : 'global';

        if (app()->runningInConsole() || request()->is('admin/*')) {
            $slug = 'all';
        }

        $cacheKey = "doctors-{$slug}";

        $doctors = Cache::remember(
            $cacheKey,
            3600,
            fn() => Doctor::query()->publiclyVisible()->with('media')->get()
        );

        // Самовосстановление на случай устаревшего пустого кеша после изменения city_doctor.
        if ($currentCity && $doctors->isEmpty()) {
            Cache::forget($cacheKey);
            $doctors = Cache::remember(
                $cacheKey,
                3600,
                fn() => Doctor::query()->publiclyVisible()->with('media')->get()
            );
        }

        return $doctors;
    }
}
