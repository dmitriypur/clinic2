<?php

namespace App\Http\Middleware;

use App\Models\City;
use App\Services\CityService;
use App\Settings\GeneralSettings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EnsureVkMiniAppRequest
{
    public function __construct(
        private readonly CityService $cityService,
        private readonly GeneralSettings $generalSettings,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->has(['vk_platform', 'vk_app_id', 'sign'])) {
            $this->logRejectedRequest($request, 'missing_required_signed_launch_params', [
                'missing' => collect(['vk_platform', 'vk_app_id', 'sign'])
                    ->reject(fn (string $key): bool => $request->has($key))
                    ->values()
                    ->all(),
            ]);

            abort(404);
        }

        if (! $this->hasValidSignature($request)) {
            $this->logRejectedRequest($request, 'invalid_launch_params_signature');

            abort(404);
        }

        if (! $request->filled('vk_group_id')) {
            $this->logRejectedRequest($request, 'missing_vk_group_id');

            abort(404);
        }

        $city = $this->resolveCityByVkGroupId((string) $request->query('vk_group_id'));
        if (! $city) {
            $this->logRejectedRequest($request, 'city_not_found_for_vk_group_id', [
                'configured_vk_group_ids' => $this->configuredVkGroupIds(),
            ]);

            abort(404);
        }

        $this->cityService->setCurrentCity($city);
        View::share('currentCity', $city);
        View::share('cities', $this->cityService->getActiveCities());

        $this->logAcceptedRequest($request, $city);

        try {
            return $next($request);
        } catch (Throwable $exception) {
            $this->logFailedRequest($request, $city, $exception);

            throw $exception;
        }
    }

    private function hasValidSignature(Request $request): bool
    {
        $secret = trim((string) ($this->generalSettings->vk_mini_app_secret ?? ''));
        $sign = (string) $request->query('sign', '');

        if ($secret === '' || $sign === '') {
            $this->logRejectedRequest($request, 'signature_check_not_possible', [
                'secret_configured' => $secret !== '',
                'sign_present' => $sign !== '',
            ]);

            return false;
        }

        $params = collect($request->query())
            ->filter(fn ($value, string $key): bool => str_starts_with($key, 'vk_') && ! is_array($value))
            ->sortKeys()
            ->all();

        if ($params === []) {
            $this->logRejectedRequest($request, 'no_vk_params_for_signature_check');

            return false;
        }

        foreach ($this->signaturePayloadVariants($params) as $query) {
            $expectedSign = $this->base64UrlEncode(hash_hmac('sha256', $query, $secret, true));

            if (hash_equals($expectedSign, $sign)) {
                return true;
            }
        }

        return false;
    }

    private function resolveCityByVkGroupId(string $groupId): ?City
    {
        $normalizedGroupId = trim($groupId);
        if ($normalizedGroupId === '') {
            return null;
        }

        return $this->cityService
            ->getActiveCities()
            ->first(function (City $city) use ($normalizedGroupId): bool {
                $cityGroupId = data_get($city->social_links, 'vk_mini_app_group_id');

                return trim((string) $cityGroupId) === $normalizedGroupId;
            });
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function signaturePayloadVariants(array $params): array
    {
        $variants = [
            http_build_query($params, '', '&', PHP_QUERY_RFC3986),
            http_build_query($params, '', '&', PHP_QUERY_RFC1738),
            collect($params)
                ->map(fn ($value, string $key): string => $key . '=' . $value)
                ->implode('&'),
        ];

        return array_values(array_unique($variants));
    }

    private function logRejectedRequest(Request $request, string $reason, array $context = []): void
    {
        Log::warning('VK Mini App appointment request rejected', array_merge([
            'reason' => $reason,
            'path' => $request->path(),
            'vk_app_id' => $request->query('vk_app_id'),
            'vk_group_id' => $request->query('vk_group_id'),
            'vk_platform' => $request->query('vk_platform'),
            'query_keys' => array_keys($request->query()),
            'sign_present' => $request->query->has('sign'),
            'sign_length' => strlen((string) $request->query('sign', '')),
            'vk_launch_params' => $this->vkLaunchParamsForLog($request),
        ], $context));
    }

    private function logAcceptedRequest(Request $request, City $city): void
    {
        Log::info('VK Mini App appointment request accepted', [
            'path' => $request->path(),
            'city_id' => $city->id,
            'city_slug' => $city->slug,
            'city_name' => $city->name,
            'vk_launch_params' => $this->vkLaunchParamsForLog($request),
        ]);
    }

    private function logFailedRequest(Request $request, City $city, Throwable $exception): void
    {
        Log::error('VK Mini App appointment request failed after validation', [
            'path' => $request->path(),
            'city_id' => $city->id,
            'city_slug' => $city->slug,
            'city_name' => $city->name,
            'exception_class' => get_class($exception),
            'exception_message' => $exception->getMessage(),
            'exception_file' => $exception->getFile(),
            'exception_line' => $exception->getLine(),
            'vk_launch_params' => $this->vkLaunchParamsForLog($request),
        ]);
    }

    private function vkLaunchParamsForLog(Request $request): array
    {
        return collect($request->query())
            ->filter(fn ($value, string $key): bool => str_starts_with($key, 'vk_') || $key === 'sign')
            ->mapWithKeys(function ($value, string $key): array {
                if ($key === 'sign') {
                    $sign = (string) $value;

                    return [
                        'sign_present' => $sign !== '',
                        'sign_length' => strlen($sign),
                    ];
                }

                return [$key => is_scalar($value) || $value === null ? $value : '[non-scalar]'];
            })
            ->all();
    }

    private function configuredVkGroupIds(): array
    {
        return $this->cityService
            ->getActiveCities()
            ->map(fn (City $city): string => trim((string) data_get($city->social_links, 'vk_mini_app_group_id')))
            ->filter()
            ->values()
            ->all();
    }
}
