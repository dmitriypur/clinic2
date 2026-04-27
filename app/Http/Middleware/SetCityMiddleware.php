<?php

namespace App\Http\Middleware;

use App\Models\City;
use App\Services\CityService;
use App\Services\GeoIpService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetCityMiddleware
{
    private const CITY_COOKIE_LIFETIME_MINUTES = 60 * 24 * 365;
    private const SELECTED_CITY_COOKIE = 'selected_city';
    private const MANUAL_CITY_OVERRIDE_COOKIE = 'manual_city_override';
    private const REMEMBERED_CITY_REDIRECT_ROUTE_NAMES = [
        'review.index',
        'doctor.show',
        'posts.show',
        'pages.show',
    ];
    private const REMEMBERED_CITY_REDIRECT_URIS = [
        'call-request',
        'sitemap.html',
    ];
    private const SKIP_MISMATCH_SESSION_KEY = 'skip_city_mismatch_once';

    public function __construct(
        protected CityService $cityService,
        protected GeoIpService $geoIpService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($redirect = $this->handleForcedCity($request)) {
            return $redirect;
        }

        if ($redirect = $this->handleUtmCityRedirect($request)) {
            return $redirect;
        }

        $citySlug = $request->route('city');

        if ($citySlug) {
            $city = $this->cityService->getCityBySlug($citySlug);

            if (!$city) {
                abort(404);
            }

            if ($this->cityService->isGlobalPath($this->pathWithoutCityPrefix($request->path(), $citySlug))) {
                return $this->redirectToUnprefixedPath($request, $citySlug, 301);
            }

            if ($redirect = $this->redirectPrefixedRouteToRememberedCity($request, $city)) {
                return $redirect;
            }

            $this->rememberCity($city);

            // Если город дефолтный, делаем редирект на URL без префикса
            if ($city->is_default) {
                return $this->redirectToUnprefixedPath($request, $citySlug, 301);
            }

            $this->forgetDetectedCity($request);
            $this->cityService->setCurrentCity($city);

            // Удаляем параметр city, чтобы он не попадал в контроллеры как аргумент
            $request->route()->forgetParameter('city');
        } else {
            $this->rememberDetectedCityMismatch($request);

            if ($redirect = $this->redirectToRememberedCityPath($request)) {
                return $redirect;
            }

            $this->cityService->setCurrentCity($this->resolveCurrentCityWithoutPrefix($request));
        }

        // Делимся текущим городом со всеми шаблонами
        View::share('currentCity', $this->cityService->getCurrentCity());
        View::share('cities', $this->cityService->getActiveCities());

        return $next($request);
    }

    protected function handleForcedCity(Request $request): ?RedirectResponse
    {
        $forcedCitySlug = $request->query('force_city');

        if (!$forcedCitySlug) {
            return null;
        }

        $forcedCity = $this->cityService->getCityBySlug($forcedCitySlug);
        if (!$forcedCity) {
            return null;
        }

        $this->rememberCity($forcedCity);
        $this->rememberManualCityOverride($forcedCity);
        $this->forgetDetectedCity($request);
        $this->markMismatchCheckAsSkipped($request);

        $segments = $request->segments();
        $activeCitySlugs = $this->cityService->getActiveCities()->pluck('slug')->all();

        if (!empty($segments) && in_array($segments[0], $activeCitySlugs, true)) {
            array_shift($segments);
        }

        $cleanPath = implode('/', $segments);
        $targetPath = $this->cityService->isGlobalPath($cleanPath)
            ? ($cleanPath ? '/' . $cleanPath : '/')
            : ($forcedCity->is_default
                ? ($cleanPath ? '/' . $cleanPath : '/')
                : '/' . $forcedCity->slug . ($cleanPath ? '/' . $cleanPath : ''));

        $query = $request->query();
        unset($query['force_city']);
        $queryString = http_build_query($query);
        $targetUrl = $targetPath . ($queryString ? '?' . $queryString : '');

        return redirect($targetUrl);
    }

    protected function handleUtmCityRedirect(Request $request): ?RedirectResponse
    {
        if ($this->cityService->isGlobalPath($request->path())) {
            return null;
        }

        if (! $request->query->has('utm_source')) {
            return null;
        }

        $activeCities = $this->cityService->getActiveCities();

        $matchedCity = $this->resolveCityByUtm(
            $activeCities,
            (string) $request->query('utm_source'),
            $request->query('utm_medium'),
        );

        if (! $matchedCity) {
            return null;
        }

        $currentRouteCitySlug = $request->route('city');
        $currentRouteCity = $currentRouteCitySlug
            ? $this->cityService->getCityBySlug($currentRouteCitySlug)
            : $this->cityService->getDefaultCity();

        if ($currentRouteCity && $currentRouteCity->id === $matchedCity->id) {
            return null;
        }

        $this->rememberCity($matchedCity);
        $this->forgetDetectedCity($request);
        $this->markMismatchCheckAsSkipped($request);

        return redirect($this->buildCityTargetUrl($request, $matchedCity, $activeCities));
    }

    private function redirectToRememberedCityPath(Request $request): ?RedirectResponse
    {
        if (!$this->isRememberedCityRedirectCandidate($request)) {
            return null;
        }

        $rememberedCity = $this->resolveRememberedCity($request);

        if (!$rememberedCity || $rememberedCity->is_default) {
            return null;
        }

        $query = $request->getQueryString();
        $path = trim($request->path(), '/');
        $target = '/' . $rememberedCity->slug . ($path === '' ? '' : '/' . $path);

        return redirect($target . ($query ? '?' . $query : ''));
    }

    private function redirectPrefixedRouteToRememberedCity(Request $request, City $requestedCity): ?RedirectResponse
    {
        $rememberedCity = $this->resolveRememberedCity($request);

        if (!$rememberedCity || $rememberedCity->id === $requestedCity->id) {
            return null;
        }

        $this->forgetDetectedCity($request);

        return redirect($this->buildCityTargetUrl(
            $request,
            $rememberedCity,
            $this->cityService->getActiveCities(),
        ));
    }

    private function resolveCityByUtm(\Illuminate\Database\Eloquent\Collection $activeCities, string $utmSource, ?string $utmMedium): ?City
    {
        $utmSource = strtolower(trim($utmSource));
        $utmMedium = strtolower(trim((string) $utmMedium));

        if ($utmSource === '') {
            return null;
        }

        $bestScore = 0;
        $matchedCity = null;
        $hasScoreTie = false;

        foreach ($activeCities as $city) {
            $score = $this->matchCityUtmScore($city, $utmSource, $utmMedium);

            if ($score === 0) {
                continue;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $matchedCity = $city;
                $hasScoreTie = false;

                continue;
            }

            if ($score === $bestScore) {
                $hasScoreTie = true;
            }
        }

        if ($hasScoreTie) {
            return null;
        }

        return $matchedCity;
    }

    private function matchCityUtmScore(City $city, string $utmSource, string $utmMedium): int
    {
        foreach (($city->utm_phones ?? []) as $rule) {
            if (strtolower((string) ($rule['source'] ?? '')) !== $utmSource) {
                continue;
            }

            if ($utmMedium !== '') {
                foreach (($rule['medium'] ?? []) as $mediumRule) {
                    if (strtolower((string) ($mediumRule['name'] ?? '')) === $utmMedium) {
                        return 2;
                    }
                }
            }

            return 1;
        }

        return 0;
    }

    private function buildCityTargetUrl(Request $request, City $city, \Illuminate\Database\Eloquent\Collection $activeCities): string
    {
        $segments = $request->segments();
        $activeCitySlugs = $activeCities->pluck('slug')->all();

        if (!empty($segments) && in_array($segments[0], $activeCitySlugs, true)) {
            array_shift($segments);
        }

        $cleanPath = implode('/', $segments);
        $targetPath = $city->is_default
            ? ($cleanPath ? '/' . $cleanPath : '/')
            : '/' . $city->slug . ($cleanPath ? '/' . $cleanPath : '');

        $queryString = http_build_query($request->query());

        return $targetPath . ($queryString ? '?' . $queryString : '');
    }

    private function resolveCurrentCityWithoutPrefix(Request $request): ?City
    {
        $defaultCity = $this->cityService->getDefaultCity();

        if (!$this->cityService->isGlobalPath($request->path())) {
            return $defaultCity;
        }

        return $this->resolveRememberedCity($request) ?? $defaultCity;
    }

    private function rememberDetectedCityMismatch(Request $request, ?City $effectiveCity = null): void
    {
        if (
            !$request->hasSession()
            || !$this->shouldCompareDetectedCity($request)
            || $this->shouldSkipMismatchCheck($request)
        ) {
            return;
        }

        $rememberedCity = $effectiveCity ?? $this->resolveRememberedCity($request);

        if (!$rememberedCity) {
            $this->forgetDetectedCity($request);

            return;
        }

        if ($this->hasManualCityOverrideForRememberedCity($request, $rememberedCity)) {
            $this->forgetDetectedCity($request);

            return;
        }

        $detectedCity = $this->detectGeoCity($request);

        if (!$detectedCity || $detectedCity->id === $rememberedCity->id) {
            $this->forgetDetectedCity($request);

            return;
        }

        $request->session()->put('detected_city', $detectedCity);
    }

    private function resolveRememberedCity(Request $request): ?City
    {
        $selectedCitySlug = $request->cookie(self::SELECTED_CITY_COOKIE);

        if (!$selectedCitySlug) {
            return null;
        }

        return $this->cityService->getCityBySlug($selectedCitySlug);
    }

    private function rememberCity(City $city): void
    {
        Cookie::queue('city_confirmed', 'true', self::CITY_COOKIE_LIFETIME_MINUTES);
        Cookie::queue(self::SELECTED_CITY_COOKIE, $city->slug, self::CITY_COOKIE_LIFETIME_MINUTES);
    }

    private function rememberManualCityOverride(City $city): void
    {
        Cookie::queue(self::MANUAL_CITY_OVERRIDE_COOKIE, $city->slug, self::CITY_COOKIE_LIFETIME_MINUTES);
    }

    private function hasManualCityOverrideForRememberedCity(Request $request, City $rememberedCity): bool
    {
        $selectedCitySlug = $request->cookie(self::SELECTED_CITY_COOKIE);
        $manualOverrideSlug = $request->cookie(self::MANUAL_CITY_OVERRIDE_COOKIE);

        return $selectedCitySlug
            && $manualOverrideSlug
            && $selectedCitySlug === $manualOverrideSlug
            && $rememberedCity->slug === $manualOverrideSlug;
    }

    private function forgetDetectedCity(Request $request): void
    {
        if ($request->hasSession()) {
            $request->session()->forget('detected_city');
        }
    }

    private function markMismatchCheckAsSkipped(Request $request): void
    {
        if ($request->hasSession()) {
            $request->session()->put(self::SKIP_MISMATCH_SESSION_KEY, true);
        }
    }

    private function isRememberedCityRedirectCandidate(Request $request): bool
    {
        if ($this->cityService->isGlobalPath($request->path())) {
            return false;
        }

        $route = $request->route();

        if (!$route) {
            return false;
        }

        if ($route->named(...self::REMEMBERED_CITY_REDIRECT_ROUTE_NAMES)) {
            return true;
        }

        return in_array($route->uri(), self::REMEMBERED_CITY_REDIRECT_URIS, true);
    }

    private function shouldCompareDetectedCity(Request $request): bool
    {
        return $this->cityService->isGlobalPath($request->path())
            || $this->isRememberedCityRedirectCandidate($request);
    }

    private function shouldSkipMismatchCheck(Request $request): bool
    {
        return (bool) $request->session()->pull(self::SKIP_MISMATCH_SESSION_KEY, false);
    }

    private function detectGeoCity(Request $request): ?City
    {
        if (config('app.env') === 'local' && $request->filled('test_city')) {
            return City::query()
                ->where('name', $request->query('test_city'))
                ->where('active', true)
                ->first();
        }

        return $this->geoIpService->getCityByIp($this->resolveClientIp($request));
    }

    private function resolveClientIp(Request $request): ?string
    {
        return $request->headers->get('CF-Connecting-IP') ?: $request->ip();
    }

    private function redirectToUnprefixedPath(Request $request, string $citySlug, int $status): RedirectResponse
    {
        $newPath = $this->pathWithoutCityPrefix($request->path(), $citySlug);
        $target = $newPath === '' ? '/' : '/' . ltrim($newPath, '/');
        $query = $request->getQueryString();

        return redirect($target . ($query ? '?' . $query : ''), $status);
    }

    private function pathWithoutCityPrefix(string $path, string $citySlug): string
    {
        return ltrim((string) preg_replace('#^' . preg_quote($citySlug, '#') . '/?#', '', $path), '/');
    }
}
