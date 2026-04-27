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
use Illuminate\Support\Facades\Cookie;
use Tests\TestCase;

class SetCityMiddlewareTest extends TestCase
{
    /** @test */
    public function root_page_redirects_to_remembered_non_default_city(): void
    {
        $rememberedCity = new City();
        $rememberedCity->slug = 'kirov';
        $rememberedCity->is_default = false;

        $cityService = $this->createMock(CityService::class);
        $cityService->expects($this->once())
            ->method('getCityBySlug')
            ->with('kirov')
            ->willReturn($rememberedCity);

        $middleware = new SetCityMiddleware($cityService, $this->createMock(GeoIpService::class));
        $request = Request::create('/', 'GET');
        $request->cookies->set('selected_city', 'kirov');

        $route = new Route('GET', '/', ['uses' => fn() => response('ok')]);
        $route->name('pages.show');
        $request->setRouteResolver(static fn() => $route->bind($request));

        $response = $middleware->handle($request, static fn() => response('ok'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(url('/kirov'), $response->getTargetUrl());
        $this->assertSame(302, $response->getStatusCode());
    }

    /** @test */
    public function doctor_page_redirects_to_remembered_non_default_city(): void
    {
        $rememberedCity = new City();
        $rememberedCity->slug = 'kirov';
        $rememberedCity->is_default = false;

        $cityService = $this->createMock(CityService::class);
        $cityService->expects($this->any())
            ->method('isGlobalPath')
            ->with('doctors/ivanov')
            ->willReturn(false);
        $cityService->expects($this->atLeastOnce())
            ->method('getCityBySlug')
            ->with('kirov')
            ->willReturn($rememberedCity);

        $middleware = new SetCityMiddleware($cityService, $this->createMock(GeoIpService::class));
        $request = Request::create('/doctors/ivanov', 'GET');
        $request->cookies->set('selected_city', 'kirov');

        $route = new Route('GET', '/doctors/{handle}', ['uses' => fn() => response('ok')]);
        $route->name('doctor.show');
        $request->setRouteResolver(static fn() => $route->bind($request));

        $response = $middleware->handle($request, static fn() => response('ok'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(url('/kirov/doctors/ivanov'), $response->getTargetUrl());
        $this->assertSame(302, $response->getStatusCode());
    }

    /** @test */
    public function force_city_query_allows_switching_root_back_to_default_city(): void
    {
        $defaultCity = new City();
        $defaultCity->slug = 'moscow';
        $defaultCity->is_default = true;

        $cityService = $this->createMock(CityService::class);
        $cityService->expects($this->once())
            ->method('getCityBySlug')
            ->with('moscow')
            ->willReturn($defaultCity);
        $cityService->expects($this->once())
            ->method('getActiveCities')
            ->willReturn(new EloquentCollection([$defaultCity]));
        $cityService->expects($this->once())
            ->method('isGlobalPath')
            ->with('')
            ->willReturn(false);

        $middleware = new SetCityMiddleware($cityService, $this->createMock(GeoIpService::class));
        $request = Request::create('/?force_city=moscow', 'GET');

        $route = new Route('GET', '/', ['uses' => fn() => response('ok')]);
        $route->name('pages.show');
        $request->setRouteResolver(static fn() => $route->bind($request));

        $response = $middleware->handle($request, static fn() => response('ok'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(url('/'), $response->getTargetUrl());
        $this->assertSame(302, $response->getStatusCode());
    }

    /** @test */
    public function force_city_query_allows_switching_doctor_page_back_to_default_city(): void
    {
        Cookie::unqueue('manual_city_override');

        $defaultCity = new City();
        $defaultCity->slug = 'moscow';
        $defaultCity->is_default = true;

        $cityService = $this->createMock(CityService::class);
        $cityService->expects($this->once())
            ->method('getCityBySlug')
            ->with('moscow')
            ->willReturn($defaultCity);
        $cityService->expects($this->once())
            ->method('getActiveCities')
            ->willReturn(new EloquentCollection([$defaultCity]));
        $cityService->expects($this->once())
            ->method('isGlobalPath')
            ->with('doctors/ivanov')
            ->willReturn(false);

        $middleware = new SetCityMiddleware($cityService, $this->createMock(GeoIpService::class));
        $request = Request::create('/doctors/ivanov?force_city=moscow', 'GET');

        $route = new Route('GET', '/doctors/{handle}', ['uses' => fn() => response('ok')]);
        $route->name('doctor.show');
        $request->setRouteResolver(static fn() => $route->bind($request));

        $response = $middleware->handle($request, static fn() => response('ok'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(url('/doctors/ivanov'), $response->getTargetUrl());
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('moscow', Cookie::queued('manual_city_override')->getValue());
    }

    /** @test */
    public function force_city_query_clears_detected_city_mismatch_in_session(): void
    {
        $defaultCity = new City();
        $defaultCity->slug = 'moscow';
        $defaultCity->is_default = true;

        $detectedCity = new City();
        $detectedCity->slug = 'samara';
        $detectedCity->is_default = false;

        $cityService = $this->createMock(CityService::class);
        $cityService->expects($this->once())
            ->method('getCityBySlug')
            ->with('moscow')
            ->willReturn($defaultCity);
        $cityService->expects($this->once())
            ->method('getActiveCities')
            ->willReturn(new EloquentCollection([$defaultCity]));
        $cityService->expects($this->once())
            ->method('isGlobalPath')
            ->with('doctors/ivanov')
            ->willReturn(false);

        $middleware = new SetCityMiddleware($cityService, $this->createMock(GeoIpService::class));
        $request = Request::create('/doctors/ivanov?force_city=moscow', 'GET');

        $session = app('session.store');
        $session->flush();
        $session->start();
        $session->put('detected_city', $detectedCity);
        $request->setLaravelSession($session);

        $route = new Route('GET', '/doctors/{handle}', ['uses' => fn() => response('ok')]);
        $route->name('doctor.show');
        $request->setRouteResolver(static fn() => $route->bind($request));

        $response = $middleware->handle($request, static fn() => response('ok'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertNull($session->get('detected_city'));
    }

    /** @test */
    public function remembered_city_redirect_keeps_detected_city_mismatch_in_session(): void
    {
        $rememberedCity = new City();
        $rememberedCity->id = 2;
        $rememberedCity->slug = 'kirov';
        $rememberedCity->is_default = false;

        $detectedCity = new City();
        $detectedCity->id = 1;
        $detectedCity->slug = 'moscow';
        $detectedCity->is_default = true;

        $cityService = $this->createMock(CityService::class);
        $cityService->expects($this->any())
            ->method('isGlobalPath')
            ->with('doctors/ivanov')
            ->willReturn(false);
        $cityService->expects($this->atLeastOnce())
            ->method('getCityBySlug')
            ->with('kirov')
            ->willReturn($rememberedCity);

        $geoIpService = $this->createMock(GeoIpService::class);
        $geoIpService->expects($this->once())
            ->method('getCityByIp')
            ->with('203.0.113.20')
            ->willReturn($detectedCity);

        $middleware = new SetCityMiddleware($cityService, $geoIpService);
        $request = Request::create('/doctors/ivanov', 'GET', [], [], [], [
            'REMOTE_ADDR' => '203.0.113.20',
        ]);
        $request->cookies->set('selected_city', 'kirov');

        $session = app('session.store');
        $session->flush();
        $session->start();
        $request->setLaravelSession($session);

        $route = new Route('GET', '/doctors/{handle}', ['uses' => fn() => response('ok')]);
        $route->name('doctor.show');
        $request->setRouteResolver(static fn() => $route->bind($request));

        $response = $middleware->handle($request, static fn() => response('ok'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(url('/kirov/doctors/ivanov'), $response->getTargetUrl());
        $this->assertSame('moscow', $session->get('detected_city')->slug);
    }

    /** @test */
    public function manual_city_override_suppresses_geo_mismatch_popup_for_same_selected_city(): void
    {
        $selectedCity = new City();
        $selectedCity->id = 1;
        $selectedCity->slug = 'moscow';
        $selectedCity->is_default = true;

        $detectedCity = new City();
        $detectedCity->id = 2;
        $detectedCity->slug = 'kirov';
        $detectedCity->is_default = false;

        $cityService = $this->createMock(CityService::class);
        $cityService->expects($this->any())
            ->method('isGlobalPath')
            ->with('doctors/ivanov')
            ->willReturn(false);
        $cityService->expects($this->exactly(2))
            ->method('getCityBySlug')
            ->with('moscow')
            ->willReturn($selectedCity);
        $cityService->expects($this->once())
            ->method('getDefaultCity')
            ->willReturn($selectedCity);
        $cityService->expects($this->once())
            ->method('setCurrentCity')
            ->with($selectedCity);
        $cityService->expects($this->once())
            ->method('getCurrentCity')
            ->willReturn($selectedCity);
        $cityService->expects($this->once())
            ->method('getActiveCities')
            ->willReturn(new EloquentCollection([$selectedCity, $detectedCity]));

        $geoIpService = $this->createMock(GeoIpService::class);
        $geoIpService->expects($this->never())
            ->method('getCityByIp');

        $middleware = new SetCityMiddleware($cityService, $geoIpService);
        $request = Request::create('/doctors/ivanov', 'GET');
        $request->cookies->set('selected_city', 'moscow');
        $request->cookies->set('manual_city_override', 'moscow');

        $session = app('session.store');
        $session->flush();
        $session->start();
        $request->setLaravelSession($session);

        $route = new Route('GET', '/doctors/{handle}', ['uses' => fn() => response('ok')]);
        $route->name('doctor.show');
        $request->setRouteResolver(static fn() => $route->bind($request));

        $response = $middleware->handle($request, static fn() => response('ok'));

        $this->assertSame('ok', $response->getContent());
        $this->assertNull($session->get('detected_city'));
    }

    /** @test */
    public function prefixed_city_page_redirects_to_remembered_city_when_cookie_differs(): void
    {
        $requestedCity = new City();
        $requestedCity->id = 1;
        $requestedCity->slug = 'moscow';
        $requestedCity->is_default = true;

        $rememberedCity = new City();
        $rememberedCity->id = 2;
        $rememberedCity->slug = 'kirov';
        $rememberedCity->is_default = false;

        $cityService = $this->createMock(CityService::class);
        $cityService->expects($this->exactly(2))
            ->method('isGlobalPath')
            ->willReturnCallback(static fn(string $path): bool => false);
        $cityService->expects($this->exactly(2))
            ->method('getCityBySlug')
            ->willReturnMap([
                ['moscow', $requestedCity],
                ['kirov', $rememberedCity],
            ]);
        $cityService->expects($this->once())
            ->method('getActiveCities')
            ->willReturn(new EloquentCollection([$requestedCity, $rememberedCity]));

        $middleware = new SetCityMiddleware($cityService, $this->createMock(GeoIpService::class));
        $request = Request::create('/moscow/doctors/ivanov', 'GET');
        $request->cookies->set('selected_city', 'kirov');

        $session = app('session.store');
        $session->flush();
        $session->start();
        $request->setLaravelSession($session);

        $route = new Route('GET', '/{city}/doctors/{handle}', ['uses' => fn() => response('ok')]);
        $route->name('doctor.show');
        $request->setRouteResolver(static fn() => $route->bind($request));

        $response = $middleware->handle($request, static fn() => response('ok'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(url('/kirov/doctors/ivanov'), $response->getTargetUrl());
    }

    /** @test */
    public function prefixed_city_page_clears_detected_city_mismatch_in_session(): void
    {
        $currentCity = new City();
        $currentCity->id = 2;
        $currentCity->slug = 'kirov';
        $currentCity->is_default = false;

        $detectedCity = new City();
        $detectedCity->id = 1;
        $detectedCity->slug = 'moscow';
        $detectedCity->is_default = true;

        $cityService = $this->createMock(CityService::class);
        $cityService->expects($this->exactly(2))
            ->method('getCityBySlug')
            ->with('kirov')
            ->willReturn($currentCity);
        $cityService->expects($this->any())
            ->method('isGlobalPath')
            ->willReturnCallback(static fn(string $path): bool => false);
        $cityService->expects($this->once())
            ->method('setCurrentCity')
            ->with($currentCity);
        $cityService->expects($this->once())
            ->method('getCurrentCity')
            ->willReturn($currentCity);
        $cityService->expects($this->once())
            ->method('getActiveCities')
            ->willReturn(new EloquentCollection([$currentCity, $detectedCity]));

        $geoIpService = $this->createMock(GeoIpService::class);
        $geoIpService->expects($this->never())
            ->method('getCityByIp');

        $middleware = new SetCityMiddleware($cityService, $geoIpService);
        $request = Request::create('/kirov/licenzii-i-iuridiceskaia-informaciia?test_city=Москва', 'GET', [], [], [], [
            'REMOTE_ADDR' => '203.0.113.21',
        ]);
        $request->cookies->set('selected_city', 'kirov');

        $session = app('session.store');
        $session->flush();
        $session->start();
        $session->put('detected_city', $detectedCity);
        $request->setLaravelSession($session);

        $route = new Route('GET', '/{city}/{handle?}', ['uses' => fn() => response('ok')]);
        $route->name('pages.show');
        $request->setRouteResolver(static fn() => $route->bind($request));

        $response = $middleware->handle($request, static fn() => response('ok'));

        $this->assertSame('ok', $response->getContent());
        $this->assertNull($session->get('detected_city'));
    }
}
