<?php

namespace App\Services;

use App\Jobs\RegenerateSitemap;
use App\Models\Doctor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DoctorImportFromBookingApiService
{
    public function import(): array
    {
        $stats = [
            'created' => 0,
            'skipped_existing' => 0,
            'skipped_missing_external_id' => 0,
            'skipped_invalid_external_id' => 0,
            'skipped_duplicate_in_api' => 0,
            'cities_total' => 0,
            'cities_processed' => 0,
            'clinics_allowed_processed' => 0,
            'doctors_received' => 0,
            'errors' => [],
        ];

        $allowedClinicIds = $this->allowedClinicIds();
        if (empty($allowedClinicIds)) {
            throw new RuntimeException(
                'Не настроены ID клиник для импорта. Укажите BOOKING_ALLOWED_CLINIC_IDS в .env'
            );
        }

        $cities = $this->fetchCities();
        $stats['cities_total'] = count($cities);

        $existingUuids = Doctor::query()
            ->whereNotNull('uuid')
            ->pluck('uuid')
            ->map(fn($value) => Str::lower((string)$value))
            ->filter()
            ->flip()
            ->all();

        $seenUuids = [];

        foreach ($cities as $city) {
            $cityId = data_get($city, 'id');
            if (!$cityId) {
                $stats['errors'][] = 'Пропущен город без id.';
                continue;
            }

            try {
                $stats['cities_processed']++;
                $cityClinics = $this->fetchCityClinics((int)$cityId);
            } catch (Throwable $e) {
                $message = "Ошибка загрузки клиник для города {$cityId}: {$e->getMessage()}";
                $stats['errors'][] = $message;
                Log::warning($message);
                continue;
            }

            $allowedClinicsInCity = collect($cityClinics)
                ->pluck('id')
                ->filter(fn($id) => is_numeric($id))
                ->map(fn($id) => (int)$id)
                ->intersect($allowedClinicIds)
                ->values()
                ->all();

            foreach ($allowedClinicsInCity as $clinicId) {
                try {
                    $apiDoctors = $this->fetchClinicDoctors((int)$clinicId);
                    $stats['clinics_allowed_processed']++;
                } catch (Throwable $e) {
                    $message = "Ошибка загрузки врачей для клиники {$clinicId}: {$e->getMessage()}";
                    $stats['errors'][] = $message;
                    Log::warning($message);
                    continue;
                }

                foreach ($apiDoctors as $apiDoctor) {
                    $stats['doctors_received']++;

                    $uuid = $this->resolveDoctorUuid($apiDoctor);
                    if ($uuid === null) {
                        $stats['skipped_missing_external_id']++;
                        continue;
                    }

                    if (!Str::isUuid($uuid)) {
                        $stats['skipped_invalid_external_id']++;
                        continue;
                    }

                    $uuid = Str::lower($uuid);

                    if (isset($seenUuids[$uuid])) {
                        $stats['skipped_duplicate_in_api']++;
                        continue;
                    }
                    $seenUuids[$uuid] = true;

                    if (isset($existingUuids[$uuid])) {
                        $stats['skipped_existing']++;
                        continue;
                    }

                    $payload = $this->mapDoctorPayload($apiDoctor, $uuid);

                    Doctor::withoutEvents(function () use ($payload): void {
                        Doctor::query()->create($payload);
                    });

                    $stats['created']++;
                    $existingUuids[$uuid] = true;
                }
            }
        }

        if ($stats['created'] > 0) {
            $this->clearDoctorsCaches();
            RegenerateSitemap::dispatch();
        }

        return $stats;
    }

    protected function fetchCities(): array
    {
        $response = Http::baseUrl($this->resolveBaseUrl())
            ->acceptJson()
            ->timeout(30)
            ->get('/cities')
            ->throw()
            ->json();

        return $this->extractList($response);
    }

    protected function fetchCityClinics(int $cityId): array
    {
        $response = Http::baseUrl($this->resolveBaseUrl())
            ->acceptJson()
            ->timeout(30)
            ->get("/cities/{$cityId}/clinics")
            ->throw()
            ->json();

        return $this->extractList($response);
    }

    protected function fetchClinicDoctors(int $clinicId): array
    {
        $response = Http::baseUrl($this->resolveBaseUrl())
            ->acceptJson()
            ->timeout(30)
            ->get("/clinics/{$clinicId}/doctors")
            ->throw()
            ->json();

        return $this->extractList($response);
    }

    protected function extractList(mixed $payload): array
    {
        if (!is_array($payload)) {
            return [];
        }

        $data = data_get($payload, 'data');
        if (is_array($data)) {
            return $data;
        }

        return array_is_list($payload) ? $payload : [];
    }

    protected function mapDoctorPayload(array $apiDoctor, string $uuid): array
    {
        $fullName = trim((string)(
            data_get($apiDoctor, 'name')
            ?: data_get($apiDoctor, 'full_name')
            ?: data_get($apiDoctor, 'fio')
            ?: ''
        ));

        $surname = trim((string)(data_get($apiDoctor, 'surname') ?: Str::before($fullName, ' ')));
        $name = trim((string)(data_get($apiDoctor, 'first_name') ?: Str::after($fullName, ' ')));

        if ($surname === '' && $fullName !== '') {
            $surname = $fullName;
        }

        if ($name === '') {
            $name = $surname !== '' ? $surname : 'Без имени';
        }

        $speciality = trim((string)(
            data_get($apiDoctor, 'speciality')
            ?: data_get($apiDoctor, 'specialization')
            ?: data_get($apiDoctor, 'specialty')
            ?: 'Специалист'
        ));

        $jobTitle = trim((string)(
            data_get($apiDoctor, 'job_title')
            ?: $speciality
            ?: 'Специалист'
        ));

        $excerpt = data_get($apiDoctor, 'excerpt')
            ?: data_get($apiDoctor, 'description_short')
            ?: null;

        $bio = trim((string)(
            data_get($apiDoctor, 'bio')
            ?: data_get($apiDoctor, 'description')
            ?: 'Информация о специалисте будет добавлена позже.'
        ));

        return [
            'uuid' => $uuid,
            'surname' => $surname,
            'name' => $name,
            'speciality' => $speciality,
            'job_title' => $jobTitle,
            'excerpt' => $excerpt,
            'bio' => $bio,
        ];
    }

    protected function resolveDoctorUuid(array $apiDoctor): ?string
    {
        $externalId = trim((string)data_get($apiDoctor, 'external_id', ''));
        return $externalId !== '' ? $externalId : null;
    }

    protected function resolveBaseUrl(): string
    {
        return 'https://adminzrenie.ru/api/v1';
    }

    protected function allowedClinicIds(): array
    {
        return collect(config('zrenie-clinic.booking_allowed_clinic_ids', []))
            ->filter(fn($id) => is_numeric($id))
            ->map(fn($id) => (int)$id)
            ->unique()
            ->values()
            ->all();
    }

    protected function clearDoctorsCaches(): void
    {
        try {
            $cityService = app(CityService::class);
            $cities = $cityService->getActiveCities();

            foreach ($cities as $city) {
                Cache::forget("doctors-{$city->slug}");
                Cache::forget("doctors-page-{$city->slug}-1");
            }

            Cache::forget('doctors-global');
            Cache::forget('doctors');
            Cache::forget('doctors-all');
        } catch (Throwable $e) {
            Log::warning('Failed to clear doctors cache after import', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
