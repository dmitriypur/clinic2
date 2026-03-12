<?php

namespace App\Http\Middleware;

use App\Services\CityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetServicesIntegrationCity
{
    public function __construct(
        protected CityService $cityService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $citySlug = $request->query('city_slug');

        if (! is_string($citySlug) || $citySlug === '') {
            $citySlug = config('services-integration.default_city_slug');
        }

        if (is_string($citySlug) && $citySlug !== '') {
            $city = $this->cityService->getCityBySlug($citySlug);

            if (! $city) {
                return response()->json([
                    'message' => 'Город не найден.',
                ], 404);
            }

            $this->cityService->setCurrentCity($city);

            return $next($request);
        }

        $this->cityService->setCurrentCity($this->cityService->getDefaultCity());

        return $next($request);
    }
}
