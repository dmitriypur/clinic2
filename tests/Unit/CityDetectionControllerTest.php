<?php

namespace Tests\Unit;

use App\Http\Controllers\CityDetectionController;
use App\Models\City;
use App\Services\CityService;
use App\Services\GeoIpService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Tests\TestCase;

class CityDetectionControllerTest extends TestCase
{
    /** @test */
    public function it_returns_detected_default_city_when_geoip_resolves_moscow(): void
    {
        $defaultCity = new City();
        $defaultCity->id = 1;
        $defaultCity->name = 'Москва';
        $defaultCity->slug = 'moscow';
        $defaultCity->is_default = true;

        $nonDefaultCity = new City();
        $nonDefaultCity->id = 2;
        $nonDefaultCity->name = 'Киров';
        $nonDefaultCity->slug = 'kirov';
        $nonDefaultCity->is_default = false;

        $geoIpService = $this->createMock(GeoIpService::class);
        $geoIpService->expects($this->once())
            ->method('getCityByIp')
            ->with('203.0.113.10')
            ->willReturn($defaultCity);

        $cityService = $this->createMock(CityService::class);
        $cityService->expects($this->once())
            ->method('getActiveCities')
            ->willReturn(new EloquentCollection([$defaultCity, $nonDefaultCity]));

        $controller = new CityDetectionController($geoIpService, $cityService);
        $request = $this->makeRequest('203.0.113.10');

        $response = $controller($request);

        $this->assertSame([
            'detectedCity' => [
                'id' => 1,
                'name' => 'Москва',
                'slug' => 'moscow',
                'is_default' => true,
            ],
        ], $response->getData(true));

        $this->assertSame('moscow', session('detected_city')->slug);
    }

    /** @test */
    public function it_returns_detected_city_when_geoip_resolves_non_default_city(): void
    {
        $defaultCity = new City();
        $defaultCity->id = 1;
        $defaultCity->name = 'Москва';
        $defaultCity->slug = 'moscow';
        $defaultCity->is_default = true;

        $nonDefaultCity = new City();
        $nonDefaultCity->id = 2;
        $nonDefaultCity->name = 'Киров';
        $nonDefaultCity->slug = 'kirov';
        $nonDefaultCity->is_default = false;

        $geoIpService = $this->createMock(GeoIpService::class);
        $geoIpService->expects($this->once())
            ->method('getCityByIp')
            ->with('203.0.113.11')
            ->willReturn($nonDefaultCity);

        $cityService = $this->createMock(CityService::class);
        $cityService->expects($this->once())
            ->method('getActiveCities')
            ->willReturn(new EloquentCollection([$defaultCity, $nonDefaultCity]));

        $controller = new CityDetectionController($geoIpService, $cityService);
        $request = $this->makeRequest('203.0.113.11');

        $response = $controller($request);

        $this->assertSame([
            'detectedCity' => [
                'id' => 2,
                'name' => 'Киров',
                'slug' => 'kirov',
                'is_default' => false,
            ],
        ], $response->getData(true));

        $this->assertSame('kirov', session('detected_city')->slug);
    }

    /** @test */
    public function it_prefers_cloudflare_connecting_ip_for_geo_detection(): void
    {
        $nonDefaultCity = new City();
        $nonDefaultCity->id = 2;
        $nonDefaultCity->name = 'Киров';
        $nonDefaultCity->slug = 'kirov';
        $nonDefaultCity->is_default = false;

        $geoIpService = $this->createMock(GeoIpService::class);
        $geoIpService->expects($this->once())
            ->method('getCityByIp')
            ->with('203.0.113.99')
            ->willReturn($nonDefaultCity);

        $cityService = $this->createMock(CityService::class);
        $cityService->expects($this->once())
            ->method('getActiveCities')
            ->willReturn(new EloquentCollection([
                (function () {
                    $defaultCity = new City();
                    $defaultCity->id = 1;
                    $defaultCity->name = 'Москва';
                    $defaultCity->slug = 'moscow';
                    $defaultCity->is_default = true;

                    return $defaultCity;
                })(),
                $nonDefaultCity,
            ]));

        $controller = new CityDetectionController($geoIpService, $cityService);
        $request = $this->makeRequest('198.51.100.20', [
            'HTTP_CF_CONNECTING_IP' => '203.0.113.99',
        ]);

        $response = $controller($request);

        $this->assertSame('kirov', $response->getData(true)['detectedCity']['slug']);
    }

    private function makeRequest(string $ip, array $server = []): Request
    {
        $request = Request::create('/city-detection', 'GET', [], [], [], array_merge([
            'REMOTE_ADDR' => $ip,
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ], $server));

        $session = app('session.store');
        $session->flush();
        $session->start();
        $request->setLaravelSession($session);

        return $request;
    }
}
