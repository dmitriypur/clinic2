<?php

namespace Tests\Unit;

use App\Http\Middleware\SetCityMiddleware;
use App\Models\City;
use App\Services\CityService;
use App\Services\GeoIpService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Tests\TestCase;

class SetCityMiddlewareUtmRedirectTest extends TestCase
{
    /** @test */
    public function root_page_redirects_to_city_matched_by_utm_medium(): void
    {
        $moscow = new City();
        $moscow->id = 1;
        $moscow->slug = 'moscow';
        $moscow->is_default = true;
        $moscow->active = true;
        $moscow->utm_phones = [];

        $kirov = new City();
        $kirov->id = 2;
        $kirov->slug = 'kirov';
        $kirov->is_default = false;
        $kirov->active = true;
        $kirov->utm_phones = [
            [
                'source' => 'google',
                'phone' => '+7 000 000-00-01',
                'medium' => [
                    [
                        'name' => 'test',
                        'phone' => '+7 000 000-00-02',
                    ],
                ],
            ],
        ];

        $cityService = $this->createMock(CityService::class);
        $cityService->expects($this->once())
            ->method('isGlobalPath')
            ->with('/')
            ->willReturn(false);
        $cityService->expects($this->once())
            ->method('getActiveCities')
            ->willReturn(new EloquentCollection([$moscow, $kirov]));
        $cityService->expects($this->once())
            ->method('getDefaultCity')
            ->willReturn($moscow);

        $middleware = new SetCityMiddleware($cityService, $this->createMock(GeoIpService::class));
        $request = Request::create('/?utm_source=google&utm_medium=test', 'GET');

        $session = app('session.store');
        $session->flush();
        $session->start();
        $request->setLaravelSession($session);

        $route = new Route('GET', '/', ['uses' => fn() => response('ok')]);
        $route->name('pages.show');
        $request->setRouteResolver(static fn() => $route->bind($request));

        $response = $middleware->handle($request, static fn() => response('ok'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(url('/kirov?utm_source=google&utm_medium=test'), $response->getTargetUrl());
        $this->assertSame(302, $response->getStatusCode());
    }

    /** @test */
    public function root_page_does_not_redirect_when_same_utm_source_exists_in_multiple_cities_without_unique_medium(): void
    {
        $moscow = new City();
        $moscow->id = 1;
        $moscow->slug = 'moscow';
        $moscow->is_default = true;
        $moscow->active = true;
        $moscow->utm_phones = [
            [
                'source' => 'google',
                'phone' => '+7 000 000-00-01',
                'medium' => [],
            ],
        ];

        $kirov = new City();
        $kirov->id = 2;
        $kirov->slug = 'kirov';
        $kirov->is_default = false;
        $kirov->active = true;
        $kirov->utm_phones = [
            [
                'source' => 'google',
                'phone' => '+7 000 000-00-02',
                'medium' => [],
            ],
        ];

        $cityService = $this->createMock(CityService::class);
        $cityService->expects($this->any())
            ->method('isGlobalPath')
            ->with('/')
            ->willReturn(false);
        $cityService->expects($this->any())
            ->method('getActiveCities')
            ->willReturn(new EloquentCollection([$moscow, $kirov]));
        $cityService->expects($this->once())
            ->method('getDefaultCity')
            ->willReturn($moscow);
        $cityService->expects($this->once())
            ->method('setCurrentCity')
            ->with($moscow);

        $middleware = new SetCityMiddleware($cityService, $this->createMock(GeoIpService::class));
        $request = Request::create('/?utm_source=google', 'GET');

        $session = app('session.store');
        $session->flush();
        $session->start();
        $request->setLaravelSession($session);

        $route = new Route('GET', '/', ['uses' => fn() => response('ok')]);
        $route->name('pages.show');
        $request->setRouteResolver(static fn() => $route->bind($request));

        $response = $middleware->handle($request, static fn() => response('ok'));

        $this->assertNotInstanceOf(RedirectResponse::class, $response);
    }
}
