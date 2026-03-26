<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Doctor;
use App\Models\Page;
use App\Services\CityService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MultiCityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Очищаем только кеш, НЕ трогаем БД
        Cache::flush();
    }

    /** @test */
    public function city_service_get_default_city_returns_default_city()
    {
        $cityService = app(CityService::class);
        $defaultCity = $cityService->getDefaultCity();

        $this->assertNotNull($defaultCity, 'В БД должен быть хотя бы один дефолтный город');
        $this->assertTrue($defaultCity->is_default);
        $this->assertTrue($defaultCity->active);
    }

    /** @test */
    public function city_service_get_active_cities_returns_only_active()
    {
        $cityService = app(CityService::class);
        $activeCities = $cityService->getActiveCities();

        $this->assertGreaterThan(0, $activeCities->count(), 'В БД должны быть активные города');

        foreach ($activeCities as $city) {
            $this->assertTrue($city->active, "Город {$city->slug} должен быть активным");
        }
    }

    /** @test */
    public function city_service_caches_city_by_slug()
    {
        $cityService = app(CityService::class);

        // Получаем первый активный город для теста
        $testCity = City::where('active', true)->first();

        if (!$testCity) {
            $this->markTestSkipped('В БД нет активных городов для тестирования');
        }

        $slug = $testCity->slug;

        // Очищаем кеш перед тестом
        Cache::forget("city_by_slug_{$slug}");

        // Первый запрос - должен обратиться к БД и закешировать
        $city1 = $cityService->getCityBySlug($slug);
        $this->assertNotNull($city1);
        $this->assertEquals($slug, $city1->slug);

        // Проверяем, что результат закеширован
        $this->assertTrue(Cache::has("city_by_slug_{$slug}"));

        // Второй запрос - должен вернуться из кеша
        $city2 = $cityService->getCityBySlug($slug);
        $this->assertEquals($city1->id, $city2->id);
    }

    /** @test */
    public function city_service_returns_null_for_invalid_slug()
    {
        $cityService = app(CityService::class);

        $city = $cityService->getCityBySlug('nonexistent-city-slug-12345');

        $this->assertNull($city);
    }

    /** @test */
    public function add_city_prefix_method_works_correctly_for_default_city()
    {
        $cityService = app(CityService::class);
        $defaultCity = $cityService->getDefaultCity();

        if (!$defaultCity) {
            $this->markTestSkipped('В БД нет дефолтного города');
        }

        $cityService->setCurrentCity($defaultCity);

        // Для дефолтного города префикс не добавляется
        $this->assertEquals('/services', $cityService->addCityPrefix('/services'));
        $this->assertEquals('/services', $cityService->addCityPrefix('services'));
        $this->assertEquals('/doctors/ivanov', $cityService->addCityPrefix('/doctors/ivanov'));
    }

    /** @test */
    public function add_city_prefix_method_works_correctly_for_non_default_city()
    {
        $cityService = app(CityService::class);

        // Находим не-дефолтный активный город
        $nonDefaultCity = City::where('active', true)
            ->where('is_default', false)
            ->first();

        if (!$nonDefaultCity) {
            $this->markTestSkipped('В БД нет не-дефолтных городов');
        }

        $cityService->setCurrentCity($nonDefaultCity);
        $slug = $nonDefaultCity->slug;

        // Для не-дефолтного города префикс добавляется
        $this->assertEquals("/{$slug}/services", $cityService->addCityPrefix('/services'));
        $this->assertEquals("/{$slug}/services", $cityService->addCityPrefix('services'));
        $this->assertEquals("/{$slug}/doctors/ivanov", $cityService->addCityPrefix('/doctors/ivanov'));

        // Тест защиты от дублирования префикса
        $this->assertEquals("/{$slug}/services", $cityService->addCityPrefix("/{$slug}/services"));
    }

    /** @test */
    public function add_city_prefix_handles_root_path_correctly()
    {
        $cityService = app(CityService::class);

        $nonDefaultCity = City::where('active', true)
            ->where('is_default', false)
            ->first();

        if (!$nonDefaultCity) {
            $this->markTestSkipped('В БД нет не-дефолтных городов');
        }

        $cityService->setCurrentCity($nonDefaultCity);
        $slug = $nonDefaultCity->slug;

        // Для корневого пути
        $this->assertEquals("/{$slug}", $cityService->addCityPrefix('/'));
        $this->assertEquals("/{$slug}", $cityService->addCityPrefix(''));
    }

    /** @test */
    public function city_route_helper_adds_prefix_for_non_default_city()
    {
        $cityService = app(CityService::class);

        $nonDefaultCity = City::where('active', true)
            ->where('is_default', false)
            ->first();

        if (!$nonDefaultCity) {
            $this->markTestSkipped('В БД нет не-дефолтных городов');
        }

        $cityService->setCurrentCity($nonDefaultCity);
        $slug = $nonDefaultCity->slug;

        $url = city_route('pages.show', ['handle' => 'services'], false);

        $this->assertStringContainsString("/{$slug}/", $url);
    }

    /** @test */
    public function city_route_helper_does_not_add_prefix_for_default_city()
    {
        $cityService = app(CityService::class);
        $defaultCity = $cityService->getDefaultCity();

        if (!$defaultCity) {
            $this->markTestSkipped('В БД нет дефолтного города');
        }

        $cityService->setCurrentCity($defaultCity);

        $url = city_route('pages.show', ['handle' => 'services'], false);

        // Не должно быть префикса города для дефолтного города
        $this->assertStringNotContainsString("/{$defaultCity->slug}/", $url);
    }

    /** @test */
    public function home_route_helper_returns_correct_url_for_non_default_city()
    {
        $cityService = app(CityService::class);

        $nonDefaultCity = City::where('active', true)
            ->where('is_default', false)
            ->first();

        if (!$nonDefaultCity) {
            $this->markTestSkipped('В БД нет не-дефолтных городов');
        }

        $cityService->setCurrentCity($nonDefaultCity);
        $slug = $nonDefaultCity->slug;

        $url = home_route();

        $this->assertEquals(url("/{$slug}"), $url);
    }

    /** @test */
    public function home_route_helper_returns_root_for_default_city()
    {
        $cityService = app(CityService::class);
        $defaultCity = $cityService->getDefaultCity();

        if (!$defaultCity) {
            $this->markTestSkipped('В БД нет дефолтного города');
        }

        $cityService->setCurrentCity($defaultCity);

        $url = home_route();

        $this->assertEquals(url('/'), $url);
    }

    /** @test */
    public function route_city_slugs_cache_contains_active_cities_only()
    {
        // Проверяем, что в кеше только активные города
        $citySlugs = Cache::remember('route_city_slugs', 3600, function () {
            return City::where('active', true)
                ->pluck('slug')
                ->map(fn($slug) => preg_quote($slug, '/'))
                ->implode('|');
        });

        $this->assertNotEmpty($citySlugs, 'Кеш city slugs не должен быть пустым');

        // Проверяем, что все активные города есть в кеше
        $activeCities = City::where('active', true)->get();
        foreach ($activeCities as $city) {
            $escapedSlug = preg_quote($city->slug, '/');
            $this->assertStringContainsString($escapedSlug, $citySlugs);
        }
    }

    /** @test */
    public function invalid_city_slug_in_url_returns_404()
    {
        $response = $this->get('/nonexistent-city-12345/services');

        $response->assertNotFound();
    }

    /** @test */
    public function default_city_with_prefix_redirects_to_url_without_prefix()
    {
        $defaultCity = City::where('is_default', true)
            ->where('active', true)
            ->first();

        if (!$defaultCity) {
            $this->markTestSkipped('В БД нет дефолтного города');
        }

        // Пробуем открыть страницу с префиксом дефолтного города
        $response = $this->get("/{$defaultCity->slug}/");

        // Должен быть редирект на URL без префикса
        $response->assertRedirect('/');
        $response->assertStatus(301);
    }

    /** @test */
    public function root_page_redirects_to_remembered_non_default_city()
    {
        $nonDefaultCity = City::where('active', true)
            ->where('is_default', false)
            ->first();

        if (!$nonDefaultCity) {
            $this->markTestSkipped('В БД нет не-дефолтных городов');
        }

        $response = $this
            ->withCookie('city_confirmed', 'true')
            ->withCookie('selected_city', $nonDefaultCity->slug)
            ->get('/');

        $response->assertRedirect("/{$nonDefaultCity->slug}");
        $response->assertStatus(302);
    }

    /** @test */
    public function non_default_city_page_loads_successfully()
    {
        $nonDefaultCity = City::where('active', true)
            ->where('is_default', false)
            ->first();

        if (!$nonDefaultCity) {
            $this->markTestSkipped('В БД нет не-дефолтных городов');
        }

        // Проверяем, что есть хотя бы одна активная страница
        $page = Page::where('active', true)->first();

        if (!$page) {
            $this->markTestSkipped('В БД нет активных страниц для тестирования');
        }

        $response = $this->get("/{$nonDefaultCity->slug}/{$page->handle}");

        $response->assertOk();
    }

    /** @test */
    public function current_city_is_shared_with_views()
    {
        $nonDefaultCity = City::where('active', true)
            ->where('is_default', false)
            ->first();

        if (!$nonDefaultCity) {
            $this->markTestSkipped('В БД нет не-дефолтных городов');
        }

        $page = Page::where('active', true)->first();

        if (!$page) {
            $this->markTestSkipped('В БД нет активных страниц');
        }

        $response = $this->get("/{$nonDefaultCity->slug}/{$page->handle}");

        $response->assertOk();
        $response->assertViewHas('currentCity');

        $currentCity = $response->viewData('currentCity');
        $this->assertEquals($nonDefaultCity->slug, $currentCity->slug);
    }

    /** @test */
    public function city_contacts_are_accessible()
    {
        $city = City::where('active', true)->first();

        if (!$city) {
            $this->markTestSkipped('В БД нет городов');
        }

        // Проверяем, что поля контактов доступны (могут быть null, но должны существовать)
        $this->assertObjectHasProperty('phone', $city);
        $this->assertObjectHasProperty('address', $city);
        $this->assertObjectHasProperty('email', $city);
        $this->assertObjectHasProperty('coordinates', $city);
        $this->assertObjectHasProperty('schedule', $city);
    }

    /** @test */
    public function has_city_scope_trait_filters_content_by_current_city()
    {
        $cityService = app(CityService::class);

        $nonDefaultCity = City::where('active', true)
            ->where('is_default', false)
            ->first();

        if (!$nonDefaultCity) {
            $this->markTestSkipped('В БД нет не-дефолтных городов');
        }

        // Устанавливаем контекст города
        $cityService->setCurrentCity($nonDefaultCity);

        // Получаем врачей с применением scope
        $doctors = Doctor::all();

        // Все врачи должны быть либо привязаны к текущему городу, либо ни к одному городу
        foreach ($doctors as $doctor) {
            $cityIds = $doctor->cities()->pluck('cities.id')->toArray();

            // Либо нет городов (глобальный врач)
            $isGlobal = empty($cityIds);

            // Либо есть текущий город в списке
            $hasCurrentCity = in_array($nonDefaultCity->id, $cityIds);

            $this->assertTrue(
                $isGlobal || $hasCurrentCity,
                "Врач {$doctor->id} не должен быть в выборке для города {$nonDefaultCity->slug}"
            );
        }
    }
}
