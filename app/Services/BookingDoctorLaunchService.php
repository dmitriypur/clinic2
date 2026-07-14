<?php

namespace App\Services;

use App\Models\City;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class BookingDoctorLaunchService
{
    private const CACHE_TTL_SECONDS = 60;

    public function __construct(
        private readonly BookingWidgetApiService $bookingWidgetApiService,
        private readonly BookingSiteDoctorsService $bookingSiteDoctorsService,
        private readonly BookingWidgetCacheVersionService $bookingWidgetCacheVersionService,
    ) {
    }

    public function getDoctorForLaunch(
        string $uuid,
        int $bookingCityId,
        ?string $birthDate,
        ?City $siteCity,
    ): ?array {
        $normalizedUuid = $this->normalizeUuid($uuid);

        if (! $normalizedUuid) {
            return null;
        }

        $cacheKey = implode(':', [
            'booking-doctor-launch',
            $bookingCityId,
            $siteCity?->id ?? 'global',
            $birthDate ?: 'all',
            $normalizedUuid,
            $this->bookingWidgetCacheVersionService->current(),
        ]);

        return Cache::remember($cacheKey, now()->addSeconds(self::CACHE_TTL_SECONDS), function () use (
            $normalizedUuid,
            $bookingCityId,
            $birthDate
        ): ?array {
            $siteDoctor = $this->getVisibleSiteDoctor($normalizedUuid);

            if (! $siteDoctor) {
                return null;
            }

            $payload = $this->bookingWidgetApiService->getDoctorsByCity($bookingCityId, $birthDate);
            $doctor = $this->findBookingDoctorByUuid(
                $this->bookingWidgetApiService->extractList($payload),
                $normalizedUuid
            );

            if (! $doctor) {
                return null;
            }

            return $this->mergeDoctorWithSiteData($doctor, $siteDoctor);
        });
    }

    private function getVisibleSiteDoctor(string $uuid): ?array
    {
        $payload = $this->bookingSiteDoctorsService->getPayloadByUuids([$uuid]);
        $doctor = data_get($payload, 'data.0');

        return is_array($doctor) ? $doctor : null;
    }

    private function findBookingDoctorByUuid(array $doctors, string $uuid): ?array
    {
        foreach ($doctors as $doctor) {
            if (! is_array($doctor)) {
                continue;
            }

            $candidate = $this->normalizeUuid(
                data_get($doctor, 'external_id') ?: data_get($doctor, 'uuid') ?: data_get($doctor, 'local_uuid')
            );

            if ($candidate === $uuid) {
                return $doctor;
            }
        }

        return null;
    }

    private function mergeDoctorWithSiteData(array $doctor, array $siteDoctor): array
    {
        return array_replace($doctor, [
            'local_uuid' => $siteDoctor['uuid'] ?? null,
            'ulid' => $siteDoctor['ulid'] ?? data_get($doctor, 'ulid'),
            'name' => $siteDoctor['full_name'] ?? data_get($doctor, 'name'),
            'full_name' => $siteDoctor['full_name'] ?? data_get($doctor, 'full_name', data_get($doctor, 'name')),
            'speciality' => $siteDoctor['speciality'] ?? data_get($doctor, 'speciality'),
            'specialization' => $siteDoctor['speciality'] ?? data_get($doctor, 'specialization'),
            'job_title' => $siteDoctor['job_title'] ?? data_get($doctor, 'job_title'),
            'excerpt' => $siteDoctor['excerpt'] ?? data_get($doctor, 'excerpt'),
            'video_url' => $siteDoctor['video_url'] ?? data_get($doctor, 'video_url'),
            'avatar_url' => $siteDoctor['avatar_url'] ?? data_get($doctor, 'avatar_url'),
            'avatar_image' => $siteDoctor['avatar_image'] ?? data_get($doctor, 'avatar_image'),
            'extra' => $siteDoctor['extra'] ?? data_get($doctor, 'extra', []),
            'age_min_months' => $siteDoctor['age_min_months']
                ?? data_get($siteDoctor, 'extra.age_min_months')
                ?? data_get($doctor, 'age_min_months')
                ?? data_get($doctor, 'extra.age_min_months'),
            'age_max_months' => $siteDoctor['age_max_months']
                ?? data_get($siteDoctor, 'extra.age_max_months')
                ?? data_get($doctor, 'age_max_months')
                ?? data_get($doctor, 'extra.age_max_months'),
            'receives_text' => $siteDoctor['receives_text']
                ?? data_get($siteDoctor, 'extra.receives_text')
                ?? data_get($doctor, 'receives_text')
                ?? data_get($doctor, 'extra.receives_text'),
            'receives_display' => $siteDoctor['receives_display'] ?? data_get($doctor, 'receives_display'),
            'seniority' => data_get($siteDoctor, 'extra.seniority') ?? data_get($doctor, 'seniority') ?? data_get($doctor, 'extra.seniority'),
        ]);
    }

    private function normalizeUuid(mixed $value): ?string
    {
        $normalized = Str::lower(trim((string) $value));

        return Str::isUuid($normalized) ? $normalized : null;
    }
}
