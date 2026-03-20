<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class BookingWidgetApiService
{
    private const CACHE_TTL_SECONDS = 60;
    private const CONNECT_TIMEOUT_SECONDS = 5;
    private const REQUEST_TIMEOUT_SECONDS = 15;

    public function getCities(): array
    {
        return $this->get('/cities');
    }

    public function getDoctorsByCity(int $cityId, ?string $birthDate = null): array
    {
        return $this->get("/cities/{$cityId}/doctors", array_filter([
            'birth_date' => $birthDate,
        ], static fn ($value) => filled($value)));
    }

    public function getClinicsByCity(int $cityId): array
    {
        return $this->get("/cities/{$cityId}/clinics");
    }

    public function getClinicBranches(int $clinicId, ?int $cityId = null): array
    {
        return $this->get("/clinics/{$clinicId}/branches", array_filter([
            'city_id' => $cityId,
        ], static fn ($value) => filled($value)));
    }

    public function getClinicBranchesBatch(array $clinicIds, ?int $cityId = null): array
    {
        $clinicIds = collect($clinicIds)
            ->filter(fn ($id): bool => is_numeric($id))
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($clinicIds->isEmpty()) {
            return [];
        }

        $results = [];
        $missingRequests = [];

        foreach ($clinicIds as $clinicId) {
            $path = "/clinics/{$clinicId}/branches";
            $query = array_filter([
                'city_id' => $cityId,
            ], static fn ($value) => filled($value));
            $cacheKey = $this->makeCacheKey($path, $query);

            if (Cache::has($cacheKey)) {
                $results[$clinicId] = (array) Cache::get($cacheKey, []);
                continue;
            }

            $missingRequests[$clinicId] = [
                'path' => $path,
                'query' => $query,
                'cache_key' => $cacheKey,
            ];
        }

        if (empty($missingRequests)) {
            return $results;
        }

        $responses = Http::pool(function (Pool $pool) use ($missingRequests) {
            $requests = [];

            foreach ($missingRequests as $clinicId => $request) {
                $requests[(string) $clinicId] = $pool
                    ->as((string) $clinicId)
                    ->acceptJson()
                    ->baseUrl($this->resolveBaseUrl())
                    ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
                    ->timeout(self::REQUEST_TIMEOUT_SECONDS)
                    ->get($request['path'], $request['query']);
            }

            return $requests;
        });

        foreach ($missingRequests as $clinicId => $request) {
            $payload = $responses[(string) $clinicId]->throw()->json();

            Cache::put($request['cache_key'], $payload, now()->addSeconds(self::CACHE_TTL_SECONDS));
            $results[$clinicId] = $payload;
        }

        return $results;
    }

    public function getClinicDoctors(int $clinicId, ?string $birthDate = null, ?int $branchId = null): array
    {
        return $this->get("/clinics/{$clinicId}/doctors", array_filter([
            'birth_date' => $birthDate,
            'branch_id' => $branchId,
        ], static fn ($value) => filled($value)));
    }

    public function extractList(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        $data = data_get($payload, 'data');

        if (is_array($data)) {
            return $data;
        }

        return array_is_list($payload) ? $payload : [];
    }

    private function get(string $path, array $query = []): array
    {
        $cacheKey = $this->makeCacheKey($path, $query);

        return Cache::remember($cacheKey, now()->addSeconds(self::CACHE_TTL_SECONDS), function () use ($path, $query): array {
            return $this->request()
                ->get($path, $query)
                ->throw()
                ->json();
        });
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl($this->resolveBaseUrl())
            ->acceptJson()
            ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
            ->timeout(self::REQUEST_TIMEOUT_SECONDS);
    }

    private function makeCacheKey(string $path, array $query = []): string
    {
        return 'booking-widget-api:' . md5($path . '|' . http_build_query($query));
    }

    private function resolveBaseUrl(): string
    {
        return rtrim((string) config('zrenie-clinic.booking_api_base_url', 'https://adminzrenie.ru/api/v1'), '/');
    }
}
