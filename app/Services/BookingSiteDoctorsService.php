<?php

namespace App\Services;

use App\Models\Doctor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class BookingSiteDoctorsService
{
    private const CACHE_TTL_SECONDS = 60;

    public function getVisibleUuidsForCity(?\App\Models\City $city): array
    {
        $cacheKey = 'booking-site-doctors:visible-uuids:' . ($city?->id ?? 'global');

        return Cache::remember($cacheKey, now()->addSeconds(self::CACHE_TTL_SECONDS), function () use ($city): array {
            $query = Doctor::withoutGlobalScopes()
                ->publiclyVisible()
                ->select('uuid');

            if ($city) {
                $query->where(function ($builder) use ($city) {
                    $builder->whereHas('cities', fn ($cityQuery) => $cityQuery->where('cities.id', $city->id))
                        ->orDoesntHave('cities');
                });
            }

            return $query->pluck('uuid')
                ->map(fn ($uuid) => Str::lower(trim((string) $uuid)))
                ->filter(fn (string $uuid) => Str::isUuid($uuid))
                ->unique()
                ->values()
                ->all();
        });
    }

    public function normalizeUuids(array $uuids): array
    {
        return collect($uuids)
            ->map(fn ($uuid) => Str::lower(trim((string) $uuid)))
            ->filter(fn (string $uuid) => Str::isUuid($uuid))
            ->unique()
            ->take(500)
            ->values()
            ->all();
    }

    public function getPayloadByUuids(array $uuids): array
    {
        $normalizedUuids = $this->normalizeUuids($uuids);

        if ($normalizedUuids === []) {
            return [
                'data' => [],
                'meta' => [
                    'hidden_uuids' => [],
                ],
            ];
        }

        $cacheKey = 'booking-site-doctors:v1:' . sha1(collect($normalizedUuids)->sort()->implode(','));

        return Cache::remember($cacheKey, now()->addSeconds(self::CACHE_TTL_SECONDS), function () use ($normalizedUuids): array {
            $allMatchedUuids = Doctor::withoutGlobalScopes()
                ->whereIn('uuid', $normalizedUuids)
                ->pluck('uuid')
                ->map(fn (string $uuid): string => Str::lower(trim($uuid)))
                ->filter()
                ->unique()
                ->values();

            $doctors = Doctor::withoutGlobalScopes()
                ->publiclyVisible()
                ->with('media')
                ->whereIn('uuid', $normalizedUuids)
                ->get()
                ->map(function (Doctor $doctor): array {
                    return [
                        'uuid' => Str::lower((string) $doctor->uuid),
                        'ulid' => $doctor->ulid,
                        'name' => $doctor->name,
                        'surname' => $doctor->surname,
                        'full_name' => trim("{$doctor->surname} {$doctor->name}"),
                        'speciality' => $doctor->speciality,
                        'job_title' => $doctor->job_title,
                        'excerpt' => $doctor->excerpt,
                        'video_url' => $doctor->actual_video_url,
                        'avatar_url' => $doctor->getSafeFirstMediaUrl('default', 'main') ?: null,
                        'avatar_image' => $doctor->avatar_image?->toHtml() ?? null,
                        'receives_display' => $doctor->receives_display,
                        'age_min_months' => $doctor->age_min_months,
                        'age_max_months' => $doctor->age_max_months,
                        'receives_text' => $doctor->receives_text,
                        'extra' => $doctor->extra ?? [],
                    ];
                })
                ->values();

            $visibleUuids = $doctors
                ->pluck('uuid')
                ->map(fn (string $uuid): string => Str::lower(trim($uuid)))
                ->filter()
                ->unique()
                ->values();

            $hiddenUuids = $allMatchedUuids
                ->diff($visibleUuids)
                ->values();

            return [
                'data' => $doctors->all(),
                'meta' => [
                    'hidden_uuids' => $hiddenUuids->all(),
                ],
            ];
        });
    }
}
