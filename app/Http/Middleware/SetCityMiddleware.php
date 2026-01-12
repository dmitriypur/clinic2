<?php

namespace App\Http\Middleware;

use App\Models\City;
use App\Services\CityService;
use App\Services\GeoIpService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetCityMiddleware
{
    public function __construct(
        protected CityService $cityService,
        protected GeoIpService $geoIpService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $citySlug = $request->route('city');

        if ($citySlug) {
            $city = $this->cityService->getCityBySlug($citySlug);

            if (!$city) {
                abort(404);
            }

            // Если город дефолтный, делаем редирект на URL без префикса
            if ($city->is_default) {
                $path = $request->path();
                // Удаляем слаг города из начала пути
                $newPath = preg_replace('#^' . preg_quote($citySlug, '#') . '/?#', '', $path);

                // Сохраняем query parameters если есть
                $query = $request->getQueryString();
                $target = '/' . $newPath . ($query ? '?' . $query : '');

                return redirect($target, 301);
            }

            $this->cityService->setCurrentCity($city);

            // Удаляем параметр city, чтобы он не попадал в контроллеры как аргумент
            $request->route()->forgetParameter('city');
        } else {
            $defaultCity = $this->cityService->getDefaultCity();
            $this->cityService->setCurrentCity($defaultCity);

            // Определение города по IP для первого визита
            if (!$request->cookie('city_confirmed')) {
                $detectedCity = null;
                // Тестовый режим для локальной разработки
                if (config('app.env') === 'local' && $request->has('test_city')) {
                    $testCityName = $request->query('test_city');
                    $detectedCity = City::where('name', $testCityName)->where('active', true)->first();
                } else {
                    $detectedCity = $this->geoIpService->getCityByIp($request->ip());
                }

                if ($detectedCity && !$detectedCity->is_default) {
                    session(['detected_city' => $detectedCity]);
                }
            }
        }

        // Делимся текущим городом со всеми шаблонами
        View::share('currentCity', $this->cityService->getCurrentCity());
        View::share('cities', $this->cityService->getActiveCities());

        return $next($request);
    }
}
