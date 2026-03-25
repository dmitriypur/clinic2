<?php

namespace App\Services;

use App\Exceptions\BookingWidgetApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BookingWidgetApiService
{
    private const CACHE_TTL_SECONDS = 60;
    private const CONNECT_TIMEOUT_SECONDS = 5;
    private const REQUEST_TIMEOUT_SECONDS = 15;
    private const RETRY_ATTEMPTS = 2;
    private const RETRY_DELAY_MILLISECONDS = 200;
    private const RETRYABLE_STATUS_CODES = [408, 429, 500, 502, 503, 504];

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
            $response = $responses[(string) $clinicId] ?? null;

            if ($response instanceof Response && $response->successful()) {
                $payload = $this->decodeResponse(
                    $response,
                    $request['path'],
                    $request['query'],
                    ['clinic_id' => $clinicId, 'mode' => 'batch']
                );
            } else {
                $payload = $this->performGet(
                    $request['path'],
                    $request['query'],
                    ['clinic_id' => $clinicId, 'mode' => 'batch-fallback']
                );
            }

            Cache::put(
                $request['cache_key'],
                $payload,
                now()->addSeconds(self::CACHE_TTL_SECONDS)
            );
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
            return $this->performGet($path, $query);
        });
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl($this->resolveBaseUrl())
            ->acceptJson()
            ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
            ->timeout(self::REQUEST_TIMEOUT_SECONDS);
    }

    private function performGet(string $path, array $query = [], array $context = []): array
    {
        $attempt = 0;

        do {
            $attempt++;
            $startedAt = microtime(true);

            try {
                $response = $this->request()->get($path, $query);

                if ($response->successful()) {
                    return $this->decodeResponse($response, $path, $query, $context + [
                        'attempt' => $attempt,
                        'duration_ms' => $this->durationInMilliseconds($startedAt),
                    ]);
                }

                if ($this->shouldRetryResponse($response, $attempt)) {
                    usleep(self::RETRY_DELAY_MILLISECONDS * 1000);
                    continue;
                }

                $this->throwApiException(
                    'Booking widget API returned an unsuccessful response.',
                    $path,
                    $query,
                    $context + [
                        'attempt' => $attempt,
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'duration_ms' => $this->durationInMilliseconds($startedAt),
                    ]
                );
            } catch (ConnectionException $exception) {
                if ($attempt <= self::RETRY_ATTEMPTS) {
                    usleep(self::RETRY_DELAY_MILLISECONDS * 1000);
                    continue;
                }

                $this->throwApiException(
                    'Booking widget API request failed due to a connection error.',
                    $path,
                    $query,
                    $context + [
                        'attempt' => $attempt,
                        'duration_ms' => $this->durationInMilliseconds($startedAt),
                    ],
                    $exception
                );
            }
        } while ($attempt <= self::RETRY_ATTEMPTS);

        $this->throwApiException(
            'Booking widget API request failed after retries.',
            $path,
            $query,
            $context + ['attempts' => $attempt]
        );
    }

    private function decodeResponse(Response $response, string $path, array $query = [], array $context = []): array
    {
        $payload = $response->json();

        if (! is_array($payload)) {
            $this->throwApiException(
                'Booking widget API returned a non-array JSON payload.',
                $path,
                $query,
                $context + [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]
            );
        }

        return $payload;
    }

    private function makeCacheKey(string $path, array $query = []): string
    {
        return 'booking-widget-api:' . md5($path . '|' . http_build_query($query));
    }

    private function resolveBaseUrl(): string
    {
        $baseUrl = rtrim((string) config('zrenie-clinic.booking_api_base_url', ''), '/');

        if ($baseUrl === '' || ! filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            $this->throwApiException(
                'Booking widget API base URL is not configured correctly.',
                '/config',
                [],
                ['configured_base_url' => $baseUrl]
            );
        }

        return $baseUrl;
    }

    private function shouldRetryResponse(Response $response, int $attempt): bool
    {
        return $attempt <= self::RETRY_ATTEMPTS
            && in_array($response->status(), self::RETRYABLE_STATUS_CODES, true);
    }

    private function durationInMilliseconds(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }

    private function throwApiException(
        string $message,
        string $path,
        array $query = [],
        array $context = [],
        ?\Throwable $previous = null
    ): never {
        $baseUrl = rtrim((string) config('zrenie-clinic.booking_api_base_url', ''), '/');

        $payload = [
            'base_url' => $baseUrl,
            'path' => $path,
            'query' => $query,
        ] + $context;

        Log::error($message, $payload + [
            'exception' => $previous?->getMessage(),
        ]);

        throw new BookingWidgetApiException($message, $payload, previous: $previous);
    }
}
