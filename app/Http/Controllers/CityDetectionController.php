<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Services\CityService;
use App\Services\GeoIpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CityDetectionController extends Controller
{
    private const SELECTED_CITY_COOKIE = 'selected_city';

    public function __construct(
        protected GeoIpService $geoIpService,
        protected CityService $cityService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        if (
            $request->cookie('city_confirmed')
            || $request->cookie(self::SELECTED_CITY_COOKIE)
            || $this->cityService->getActiveCities()->count() <= 1
        ) {
            $request->session()->forget('detected_city');

            return response()->json(['detectedCity' => null]);
        }

        $detectedCity = session('detected_city');

        if (!$detectedCity instanceof City) {
            $detectedCity = $this->detectCity($request);

            if ($detectedCity) {
                session(['detected_city' => $detectedCity]);
            }
        }

        if (!$detectedCity) {
            return response()->json(['detectedCity' => null]);
        }

        return response()->json([
            'detectedCity' => [
                'id' => $detectedCity->id,
                'name' => $detectedCity->name,
                'slug' => $detectedCity->slug,
                'is_default' => (bool) $detectedCity->is_default,
            ],
        ]);
    }

    private function detectCity(Request $request): ?City
    {
        $detectedCity = null;

        if (config('app.env') === 'local' && $request->filled('test_city')) {
            $detectedCity = City::query()
                ->where('name', $request->query('test_city'))
                ->where('active', true)
                ->first();
        } else {
            $detectedCity = $this->geoIpService->getCityByIp($request->ip());
        }

        // Автодетект нужен только для non-default города.
        // Дефолтный город и любые нерелевантные IP остаются на сайте без popup.
        if (!$detectedCity || $detectedCity->is_default) {
            return null;
        }

        return $detectedCity;
    }
}
