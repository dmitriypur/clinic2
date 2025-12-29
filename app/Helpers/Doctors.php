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

        return Cache::remember("doctors-{$slug}", 3600, fn() => Doctor::query()->with('media')->get());
    }
}
