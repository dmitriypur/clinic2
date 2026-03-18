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

    public function __construct(
        protected CityService $cityService,
        protected GeoIpService $geoIpService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($redirect = $this->handleForcedCity($request)) {
            return $redirect;
        }

        $citySlug = $request->route('city');

        if ($citySlug) {
            $city = $this->cityService->getCityBySlug($citySlug);

            if (!$city) {
                abort(404);
            }

            $this->rememberCity($city);

            if ($this->cityService->isGlobalPath($this->pathWithoutCityPrefix($request->path(), $citySlug))) {
                return $this->redirectToUnprefixedPath($request, $citySlug, 301);
            }

            // Если город дефолтный, делаем редирект на URL без префикса
            if ($city->is_default) {
                return $this->redirectToUnprefixedPath($request, $citySlug, 301);
            }

            $this->cityService->setCurrentCity($city);

            // Удаляем параметр city, чтобы он не попадал в контроллеры как аргумент
            $request->route()->forgetParameter('city');
        } else {
            $this->cityService->setCurrentCity($this->resolveCurrentCityWithoutPrefix($request));

            // Определение города по IP для первого визита
            if (
                !$request->cookie('city_confirmed')
                && !$request->cookie(self::SELECTED_CITY_COOKIE)
                && $this->cityService->getActiveCities()->count() > 1
            ) {
                $detectedCity = null;
                // Тестовый режим для локальной разработки
                if (config('app.env') === 'local' && $request->has('test_city')) {
                    $testCityName = $request->query('test_city');
                    $detectedCity = City::where('name', $testCityName)->where('active', true)->first();
                } else {
                    $detectedCity = $this->geoIpService->getCityByIp($request->ip());
                }

                \Log::info('City detection', [
                    'ip' => $request->ip(),
                    'detected_city' => $detectedCity ? $detectedCity->name : null,
                    'is_default' => $detectedCity ? $detectedCity->is_default : null,
                    'cookie_exists' => $request->cookie('city_confirmed') ? 'yes' : 'no'
                ]);

                if ($detectedCity) {
                    session(['detected_city' => $detectedCity]);
                    \Log::info('Detected city saved to session', ['city' => $detectedCity->name]);
                }
            }
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

    private function resolveCurrentCityWithoutPrefix(Request $request): ?City
    {
        $defaultCity = $this->cityService->getDefaultCity();

        if (!$this->cityService->isGlobalPath($request->path())) {
            return $defaultCity;
        }

        return $this->resolveRememberedCity($request) ?? $defaultCity;
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
