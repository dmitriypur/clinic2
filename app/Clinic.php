<?php

namespace App;

use App\Contracts\Services\Schema\Schema;
use App\Models\Review;
use App\Settings\SeoSettings;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\HtmlableMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Clinic
{
    public static PendingRequest $http;

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

    public static function callback(array $data): void
    {
        self::$http->post(config('zrenie-clinic.urls.callback'), $data);
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
        $cityService = app(\App\Services\CityService::class);
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


        $queryString = request()->getQueryString();
        $query = $queryString ? '?' . $queryString : '';

        $preparedCities = $cities->map(function($city) use ($preparedPath, $query, $currentCity) {
            $path = $preparedPath ? '/' . $preparedPath : '';
            $url = $city->is_default
                ? url($path . $query)
                : url($city->slug . $path . $query);

            return [
                'id' => $city->id,
                'name' => $city->name,
                'slug' => $city->slug,
                'url' => $url,
                'is_current' => $currentCity && $currentCity->id === $city->id
            ];
        })->values();

        $detectedCity = session()->get('detected_city');
        if ($detectedCity) {
            $path = $preparedPath ? '/' . $preparedPath : '';
            $detectedCity->url = $detectedCity->is_default
                ? url($path . $query)
                : url($detectedCity->slug . $path . $query);
        }

        return [
            'csrfToken' => csrf_token(),
            'env' => config('app.env'),
            'baseUrl' => url('/'),
            'state' => resolve(InitialFrontendState::class)->forUser(Auth::user()),
            'detectedCity' => $detectedCity,
            'cities' => $preparedCities,
        ];
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
