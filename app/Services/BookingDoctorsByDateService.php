<?php

namespace App\Services;

use App\Models\City;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class BookingDoctorsByDateService
{
    private const CACHE_TTL_SECONDS = 60;

    public function __construct(
        private readonly BookingWidgetApiService $bookingWidgetApiService,
        private readonly BookingBranchEnrichmentService $bookingBranchEnrichmentService,
        private readonly BookingSiteDoctorsService $bookingSiteDoctorsService,
        private readonly BookingWidgetCacheVersionService $bookingWidgetCacheVersionService,
    ) {
    }

    public function getDoctorsForDate(
        int $bookingCityId,
        string $date,
        ?string $birthDate,
        ?City $siteCity,
        ?int $clinicId = null,
        ?int $branchId = null,
    ): array {
        $cacheKey = $this->makeCacheKey('day', $bookingCityId, $date, $birthDate, $siteCity?->id, $clinicId, $branchId);

        return Cache::remember($cacheKey, now()->addSeconds(self::CACHE_TTL_SECONDS), function () use (
            $bookingCityId,
            $date,
            $birthDate,
            $siteCity,
            $clinicId,
            $branchId
        ): array {
            $payload = $this->bookingWidgetApiService->getDoctorsByDate(
                $bookingCityId,
                $date,
                $birthDate,
                $clinicId,
                $branchId
            );

            return [
                'data' => $this->enrichDoctorEntries(
                    $this->bookingWidgetApiService->extractList($payload),
                    $siteCity
                ),
            ];
        });
    }

    public function getCalendarAvailability(
        int $bookingCityId,
        string $dateFrom,
        string $dateTo,
        ?string $birthDate,
        ?City $siteCity,
        ?int $clinicId = null,
        ?int $branchId = null,
    ): array {
        $cacheKey = $this->makeCacheKey('calendar', $bookingCityId, $dateFrom . ':' . $dateTo, $birthDate, $siteCity?->id, $clinicId, $branchId);

        return Cache::remember($cacheKey, now()->addSeconds(self::CACHE_TTL_SECONDS), function () use (
            $bookingCityId,
            $dateFrom,
            $dateTo,
            $birthDate,
            $siteCity,
            $clinicId,
            $branchId
        ): array {
            $dates = $this->buildDateRange($dateFrom, $dateTo);
            $visibleDoctorUuids = $this->bookingSiteDoctorsService->getVisibleUuidsForCity($siteCity);

            if ($visibleDoctorUuids === []) {
                return ['data' => $this->buildEmptyCalendarItems($dates)];
            }

            $payload = $this->bookingWidgetApiService->getDoctorsByDateCalendarAvailability(
                $bookingCityId,
                $dateFrom,
                $dateTo,
                $birthDate,
                $visibleDoctorUuids,
                $clinicId,
                $branchId
            );

            $items = collect($dates)
                ->mapWithKeys(fn (string $date): array => [
                    $date => [
                        'date' => $date,
                        'total_slots' => 0,
                        'available_slots' => 0,
                        'available_doctors' => 0,
                        'first_available_time' => null,
                    ],
                ]);

            foreach ($this->bookingWidgetApiService->extractList($payload) as $item) {
                if (! is_array($item) || blank($item['date'] ?? null)) {
                    continue;
                }

                $date = (string) $item['date'];
                if (! $items->has($date)) {
                    continue;
                }

                $items[$date] = [
                    'date' => $date,
                    'total_slots' => (int) data_get($item, 'total_slots', 0),
                    'available_slots' => (int) data_get($item, 'available_slots', 0),
                    'available_doctors' => (int) data_get($item, 'available_doctors', 0),
                    'first_available_time' => data_get($item, 'first_available_time'),
                ];
            }

            return ['data' => $items->values()->all()];
        });
    }

    private function enrichDoctorEntries(array $entries, ?City $siteCity, ?array $siteDoctorsByUuid = null): array
    {
        if ($entries === []) {
            return [];
        }

        $siteDoctorsByUuid = $siteDoctorsByUuid ?? $this->buildSiteDoctorsMap($entries);

        return collect($entries)
            ->map(function ($entry) use ($siteDoctorsByUuid, $siteCity) {
                if (! is_array($entry)) {
                    return null;
                }

                $uuid = $this->normalizeUuid(data_get($entry, 'external_id') ?: data_get($entry, 'uuid'));
                $siteDoctor = $uuid ? ($siteDoctorsByUuid[$uuid] ?? null) : null;

                if (! is_array($siteDoctor)) {
                    return null;
                }

                $branch = $this->buildBranchPayload($entry, $siteCity);

                return [
                    'id' => (string) (data_get($entry, 'id') ?: implode('-', [
                        data_get($entry, 'doctor_id', 'doctor'),
                        data_get($entry, 'branch_id', 'branch'),
                        data_get($entry, 'date', 'date'),
                    ])),
                    'entry_key' => (string) (data_get($entry, 'id') ?: implode('-', [
                        data_get($entry, 'doctor_id', 'doctor'),
                        data_get($entry, 'branch_id', 'branch'),
                        data_get($entry, 'date', 'date'),
                    ])),
                    'doctor_id' => data_get($entry, 'doctor_id'),
                    'clinic_id' => data_get($entry, 'clinic_id'),
                    'branch_id' => data_get($entry, 'branch_id'),
                    'date' => data_get($entry, 'date'),
                    'name' => $siteDoctor['full_name'] ?? data_get($entry, 'name'),
                    'full_name' => $siteDoctor['full_name'] ?? data_get($entry, 'name'),
                    'speciality' => $siteDoctor['speciality'] ?? data_get($entry, 'speciality'),
                    'specialization' => $siteDoctor['speciality'] ?? data_get($entry, 'speciality'),
                    'job_title' => $siteDoctor['job_title'] ?? null,
                    'experience' => data_get($entry, 'experience'),
                    'age' => data_get($entry, 'age'),
                    'photo_src' => data_get($entry, 'photo_src'),
                    'diploma_src' => data_get($entry, 'diploma_src'),
                    'status' => data_get($entry, 'status'),
                    'age_admission_from' => data_get($entry, 'age_admission_from'),
                    'age_admission_to' => data_get($entry, 'age_admission_to'),
                    'uuid' => $siteDoctor['uuid'] ?? data_get($entry, 'uuid'),
                    'external_id' => data_get($entry, 'external_id'),
                    'ulid' => $siteDoctor['ulid'] ?? null,
                    'review_link' => data_get($entry, 'review_link'),
                    'avatar_url' => $siteDoctor['avatar_url'] ?? null,
                    'avatar_image' => $siteDoctor['avatar_image'] ?? null,
                    'video_url' => $siteDoctor['video_url'] ?? null,
                    'excerpt' => $siteDoctor['excerpt'] ?? null,
                    'receives_display' => $siteDoctor['receives_display'] ?? null,
                    'age_min_months' => $siteDoctor['age_min_months'] ?? data_get($siteDoctor, 'extra.age_min_months'),
                    'age_max_months' => $siteDoctor['age_max_months'] ?? data_get($siteDoctor, 'extra.age_max_months'),
                    'receives_text' => $siteDoctor['receives_text'] ?? data_get($siteDoctor, 'extra.receives_text'),
                    'extra' => $siteDoctor['extra'] ?? [],
                    'available_slots' => (int) data_get($entry, 'available_slots', 0),
                    'first_available_time' => data_get($entry, 'first_available_time'),
                    'branch' => $branch,
                    'branch_name' => data_get($branch, 'name', data_get($entry, 'branch_name')),
                    'branch_address' => data_get($branch, 'address', data_get($entry, 'branch_address')),
                    'clinic' => [
                        'id' => data_get($entry, 'clinic_id'),
                        'name' => data_get($entry, 'clinic_name'),
                    ],
                    'clinic_name' => data_get($entry, 'clinic_name'),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function buildSiteDoctorsMap(array $entries): array
    {
        $uuids = collect($entries)
            ->map(fn ($entry) => $this->normalizeUuid(data_get($entry, 'external_id') ?: data_get($entry, 'uuid')))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $payload = $this->bookingSiteDoctorsService->getPayloadByUuids($uuids);

        return collect(data_get($payload, 'data', []))
            ->filter(fn ($doctor): bool => is_array($doctor) && filled($doctor['uuid'] ?? null))
            ->mapWithKeys(fn (array $doctor): array => [
                Str::lower(trim((string) $doctor['uuid'])) => $doctor,
            ])
            ->all();
    }

    private function buildBranchPayload(array $entry, ?City $siteCity): array
    {
        $branch = [
            'id' => data_get($entry, 'branch_id'),
            'external_id' => data_get($entry, 'branch_external_id')
                ?: data_get($entry, 'branch.external_id')
                ?: data_get($entry, 'branch.externalId'),
            'name' => data_get($entry, 'branch_name'),
            'title' => data_get($entry, 'branch_name'),
            'address' => data_get($entry, 'branch_address'),
            'metro' => data_get($entry, 'branch_metro') ?: data_get($entry, 'branch.metro'),
            'city' => $siteCity?->name,
        ];

        $enriched = $this->bookingBranchEnrichmentService->enrichBranches([$branch], $siteCity);

        return is_array($enriched[0] ?? null) ? $enriched[0] : $branch;
    }

    private function normalizeUuid(mixed $value): ?string
    {
        $normalized = Str::lower(trim((string) $value));

        return Str::isUuid($normalized) ? $normalized : null;
    }

    private function buildDateRange(string $dateFrom, string $dateTo): array
    {
        $start = \Carbon\Carbon::createFromFormat('Y-m-d', $dateFrom)->startOfDay();
        $end = \Carbon\Carbon::createFromFormat('Y-m-d', $dateTo)->startOfDay();

        $dates = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $dates[] = $cursor->format('Y-m-d');
            $cursor->addDay();
        }

        return $dates;
    }

    private function buildEmptyCalendarItems(array $dates): array
    {
        return collect($dates)
            ->map(fn (string $date): array => [
                'date' => $date,
                'total_slots' => 0,
                'available_slots' => 0,
                'available_doctors' => 0,
                'first_available_time' => null,
            ])
            ->values()
            ->all();
    }

    private function makeCacheKey(
        string $prefix,
        int $bookingCityId,
        string $date,
        ?string $birthDate,
        ?int $siteCityId,
        ?int $clinicId,
        ?int $branchId,
    ): string {
        return implode(':', [
            'booking-doctors-by-date',
            $prefix,
            $bookingCityId,
            $date,
            $birthDate ?: 'all',
            $siteCityId ?: 'site-any',
            $clinicId ?: 'clinic-any',
            $branchId ?: 'branch-any',
            $this->bookingWidgetCacheVersionService->current(),
        ]);
    }
}
