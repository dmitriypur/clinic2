<?php

namespace App;

use App\Contracts\Services\Schema\Schema;
use App\Models\Review;
use App\Services\BookingWidgetOrderingService;
use App\Settings\GeneralSettings;
use App\Settings\SeoSettings;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\HtmlableMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Clinic
{
    public static PendingRequest $http;
    private const UTM_KEYS = [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
    ];

    public static function setHttp(): void
    {
        self::$http = Http::baseUrl(config('zrenie-clinic.base_url'))
            ->withHeaders([
                'X-LO-Token' => config('zrenie-clinic.lo_token'),
            ]);
    }

    public static function makingAnAppointment(array $data): void
    {
        self::$http->post(config('zrenie-clinic.urls.appointment'), $data);
    }

    public static function callback(array $data): Response
    {
        return self::$http->post(config('zrenie-clinic.urls.callback'), $data);
    }

    public static function sendForm(array $data): void
    {
        self::$http->post(config('zrenie-clinic.urls.form'), $data);
    }


    public static function getUser(string|int $id): mixed
    {
        return self::$http->post(config('zrenie-clinic.urls.profile'), ['uid' => $id])->json();
    }

    public static function prices(): array
    {
//        dd(self::$http->post(config('zrenie-clinic.urls.services'))->json());
//        return self::$http->post(config('zrenie-clinic.urls.services'))->json() ?? [];
        return Cache::remember('prices', 2592000, fn() => self::$http->post(config('zrenie-clinic.urls.services'))->json() ?? []);
    }

    public static function verificationCode(string|int $id): void
    {
        self::$http->post(config('zrenie-clinic.urls.profile'), ['uid' => $id]);
    }

    public static function schedule()
    {
        return self::$http->post(config('zrenie-clinic.urls.schedule'))->json() ?? [];
    }


    public static function scriptVariables(): array
    {
        $generalSettings = app(GeneralSettings::class);
        $cityService = app(\App\Services\CityService::class);
        $bookingWidgetOrderingService = app(BookingWidgetOrderingService::class);
        $currentCity = $cityService->getCurrentCity();
        $cities = $cityService->getActiveCities();

        $currentPath = request()->path();
        // home_route() возвращает '/' для дефолтного города, и '/slug' для других.
        // request()->path() возвращает '/' для главной страницы дефолтного города, и 'slug' для главной другого города.
        // приведем все к одному виду - без слеша в конце, кроме корневого '/', чтобы substr не отрезал лишнее.
        if ($currentPath !== '/') {
            $currentPath = rtrim($currentPath, '/');
        }

        $preparedPath = $currentPath;
        if ($currentCity && !$currentCity->is_default) {
            $prefix = $currentCity->slug;
            if (str_starts_with($preparedPath, $prefix)) {
                $preparedPath = substr($preparedPath, strlen($prefix));
            }
        }
        $preparedPath = ltrim($preparedPath, '/');


        $queryParams = request()->query();
        unset($queryParams['force_city']);
        unset($queryParams['test_city']);
        $isGlobalPath = $cityService->isGlobalPath($preparedPath);

        $preparedCities = $cities->map(function($city) use ($preparedPath, $queryParams, $currentCity, $isGlobalPath) {
            $path = $preparedPath ? '/' . $preparedPath : '';
            $forcedCityQuery = array_merge($queryParams, ['force_city' => $city->slug]);
            $url = url($path ?: '/') . '?' . http_build_query($forcedCityQuery);

            return [
                'id' => $city->id,
                'name' => $city->name,
                'slug' => $city->slug,
                'url' => $url,
                'is_default' => (bool) $city->is_default,
                'is_current' => $currentCity && $currentCity->id === $city->id
            ];
        })->values();

        $detectedCity = request()->hasSession() ? session()->get('detected_city') : null;
        $hasConfirmedCity = request()->cookie('city_confirmed') || request()->cookie('selected_city');

        if (
            $detectedCity
            && $currentCity
            && $detectedCity->id === $currentCity->id
            && request()->hasSession()
            && ($hasConfirmedCity || !$currentCity->is_default)
        ) {
            request()->session()->forget('detected_city');
            $detectedCity = null;
        }

        if ($detectedCity) {
            $path = $preparedPath ? '/' . $preparedPath : '';
            $detectedCity->url = $detectedCity->is_default
                ? url($path)
                : url($detectedCity->slug . $path);

            if ($isGlobalPath) {
                $globalQuery = array_merge($queryParams, ['force_city' => $detectedCity->slug]);
                $detectedCity->url = url($path ?: '/') . (count($globalQuery) ? '?' . http_build_query($globalQuery) : '');
            } elseif (count($queryParams)) {
                $detectedCity->url .= '?' . http_build_query($queryParams);
            }
        }

        $utmParameters = self::frontendUtmParameters();

        return [
            'csrfToken' => csrf_token(),
            'env' => config('app.env'),
            'baseUrl' => url('/'),
            'cityConfirmed' => (bool) (request()->cookie('city_confirmed') || request()->cookie('selected_city')),
            'state' => resolve(InitialFrontendState::class)->forUser(Auth::user()),
            'utm' => $utmParameters,
            'detectedCity' => $detectedCity,
            'cities' => $preparedCities,
            'booking' => [
                'siteCityId' => $currentCity?->id,
                'allowedClinicIds' => config('zrenie-clinic.booking_allowed_clinic_ids', []),
                'formVariant' => $generalSettings->booking_form_variant ?? 'old',
                'doctorSortOrders' => $bookingWidgetOrderingService->getDoctorOrderMapForCity($currentCity?->id),
                'doctorSelectSortOrders' => $bookingWidgetOrderingService->getDoctorOrderMapForCity($currentCity?->id),
                'clinicDoctorSortOrders' => $bookingWidgetOrderingService->getClinicDoctorOrderMapForCity($currentCity?->id),
                'branchSortOrders' => $bookingWidgetOrderingService->getBranchOrderMapForCity($currentCity?->id),
            ],
        ];
    }

    public static function frontendUtmParameters(): array
    {
        $utmParameters = [];

        foreach (self::UTM_KEYS as $key) {
            $value = request()->query($key, request()->hasSession() ? request()->session()->get($key) : null);

            if (filled($value)) {
                $utmParameters[$key] = $value;
            }
        }

        return $utmParameters !== []
            ? $utmParameters
            : ['default_site' => 'organic'];
    }

    public static function schema(): Schema
    {
        return app(Schema::class);
    }

    public static function relativeUrl(string $url): string
    {
        return str_replace(config('app.url'), '', $url);
    }

    public static function responsiveImage(?Media $media, $title): ?HtmlableMedia
    {
        $settings = app(SeoSettings::class);

        return $media?->img('main')->attributes([
            'alt' => Str::of($settings->image_alt_template)->replace('{h1}', $title)->trim()->value(),
            'title' => Str::of($settings->image_title_template)->replace('{h1}', $title)->trim()->value(),
            'itemprop' => 'contentUrl'
        ]);
    }


    public static string $version = '1.0.0';
}
