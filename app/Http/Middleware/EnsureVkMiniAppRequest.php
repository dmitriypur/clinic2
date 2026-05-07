<?php

namespace App\Http\Middleware;

use App\Models\City;
use App\Services\CityService;
use App\Settings\GeneralSettings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class EnsureVkMiniAppRequest
{
    public function __construct(
        private readonly CityService $cityService,
        private readonly GeneralSettings $generalSettings,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->has(['vk_platform', 'vk_app_id', 'vk_group_id', 'sign'])) {
            abort(404);
        }

        if (! $this->hasValidSignature($request)) {
            abort(404);
        }

        $city = $this->resolveCityByVkGroupId((string) $request->query('vk_group_id'));
        if (! $city) {
            abort(404);
        }

        $this->cityService->setCurrentCity($city);
        View::share('currentCity', $city);
        View::share('cities', $this->cityService->getActiveCities());

        return $next($request);
    }

    private function hasValidSignature(Request $request): bool
    {
        $secret = trim((string) ($this->generalSettings->vk_mini_app_secret ?? ''));
        $sign = (string) $request->query('sign', '');

        if ($secret === '' || $sign === '') {
            return false;
        }

        $params = collect($request->query())
            ->filter(fn ($value, string $key): bool => str_starts_with($key, 'vk_') && ! is_array($value))
            ->sortKeys()
            ->all();

        if ($params === []) {
            return false;
        }

        $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $expectedSign = $this->base64UrlEncode(hash_hmac('sha256', $query, $secret, true));

        return hash_equals($expectedSign, $sign);
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
}
