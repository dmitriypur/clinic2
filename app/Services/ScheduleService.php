<?php

namespace App\Services;

use App\Clinic;
use App\Contracts\Services\ScheduleService as Contract;
use App\Models\Doctor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ScheduleService implements Contract
{
    public function get(): array
    {
        $cityService = app(CityService::class);
        $currentCity = $cityService->getCurrentCity() ?? $cityService->getDefaultCity();

        $doctorsQuery = Doctor::withoutGlobalScopes()
            ->publiclyVisible();

        if ($currentCity) {
            $doctorsQuery->where(function (Builder $query) use ($currentCity): void {
                $query->whereHas('cities', function (Builder $cityQuery) use ($currentCity): void {
                    $cityQuery->where('cities.id', $currentCity->id);
                })->orDoesntHave('cities');
            });
        } else {
            $doctorsQuery->whereDoesntHave('cities');
        }

        $doctorsByUuid = $doctorsQuery
            ->with('media')
            ->get()
            ->keyBy(fn($doctor) => Str::lower(trim((string) $doctor->uuid)));

        return collect(Clinic::schedule())
            ->where(fn($item) => isset($item['schedule']['data'][config('zrenie-clinic.clinic_uuid')]))
            ->flatMap(fn($item) => $item['schedule']['data'][config('zrenie-clinic.clinic_uuid')])
            ->map(function ($item, $key) use ($doctorsByUuid) {
                $doctor = $doctorsByUuid->get(Str::lower(trim((string) $key)));
                if (!$doctor) {
                    return null;
                }

                return [
                    'iddb' => $doctor->id,
                    'id' => $doctor->uuid,
                    'video_url' => $doctor->actual_video_url,
                    'avatar_image' => $doctor->avatar_image?->toHtml() ?? null,
                    'name' => $item['efio'],
                    'speciality' => $item['espec'],
                    'cells' => $item['cells'],
                    'receives_display' => $doctor->receives_display,
                    'age_min_months' => $doctor->age_min_months,
                    'age_max_months' => $doctor->age_max_months,
                    'receives_text' => $doctor->receives_text,
                    'seniority' => $doctor->extra['seniority'],
                ];
            })
            ->sortBy('iddb')
            ->filter()
            ->values()
            ->toArray();
    }

}
