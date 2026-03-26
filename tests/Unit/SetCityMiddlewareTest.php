<?php

namespace Tests\Unit;

use App\Http\Middleware\SetCityMiddleware;
use App\Models\City;
use App\Services\CityService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
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

        $middleware = new SetCityMiddleware($cityService);
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
        $cityService->expects($this->once())
            ->method('isGlobalPath')
            ->with('doctors/ivanov')
            ->willReturn(false);
        $cityService->expects($this->once())
            ->method('getCityBySlug')
            ->with('kirov')
            ->willReturn($rememberedCity);

        $middleware = new SetCityMiddleware($cityService);
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

        $middleware = new SetCityMiddleware($cityService);
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

        $middleware = new SetCityMiddleware($cityService);
        $request = Request::create('/doctors/ivanov?force_city=moscow', 'GET');

        $route = new Route('GET', '/doctors/{handle}', ['uses' => fn() => response('ok')]);
        $route->name('doctor.show');
        $request->setRouteResolver(static fn() => $route->bind($request));

        $response = $middleware->handle($request, static fn() => response('ok'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(url('/doctors/ivanov'), $response->getTargetUrl());
        $this->assertSame(302, $response->getStatusCode());
    }
}
