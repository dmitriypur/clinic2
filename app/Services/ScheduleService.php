<?php

namespace App\Services;

use App\Clinic;
use App\Contracts\Services\ScheduleService as Contract;
use App\Models\Doctor;
use Illuminate\Support\Facades\Cache;

class ScheduleService implements Contract
{
    public function get(): array
    {
        $doctors = Cache::remember('doctors', 3600, fn() => Doctor::all());
        return collect(Clinic::schedule())->where(fn($item) => isset($item['schedule']['data'][config('zrenie-clinic.clinic_uuid')]))
            ->flatMap(fn($item) => $item['schedule']['data'][config('zrenie-clinic.clinic_uuid')])
            ->map(function ($item, $key) use ($doctors) {
                $doctor = $doctors->firstWhere('uuid', $key);
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
                    'receives' => $doctor->extra['receives'],
                    'seniority' => $doctor->extra['seniority'],
                ];
            })
            ->sortBy('iddb')
            ->filter()
            ->values()
            ->toArray();
    }

}
