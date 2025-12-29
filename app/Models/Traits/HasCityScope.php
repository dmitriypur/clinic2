<?php

namespace App\Models\Traits;

use App\Services\CityService;
use Illuminate\Database\Eloquent\Builder;

trait HasCityScope
{
    public static function bootHasCityScope(): void
    {
        if (app()->runningInConsole() || request()->is('admin/*') || request()->is('livewire/*')) {
            return;
        }

        static::addGlobalScope('city', function (Builder $builder) {
            $cityService = app(CityService::class);
            $currentCity = $cityService->getCurrentCity();

            if ($currentCity) {
                $builder->where(function (Builder $query) use ($currentCity) {
                    $query->whereHas('cities', function (Builder $q) use ($currentCity) {
                        $q->where('cities.id', $currentCity->id);
                    })->orDoesntHave('cities');
                });
            } else {
                $builder->doesntHave('cities');
            }
        });
    }
}
