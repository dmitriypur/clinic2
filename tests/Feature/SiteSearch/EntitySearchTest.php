<?php

namespace Tests\Feature\SiteSearch;

use App\Models\City;
use App\Models\Doctor;
use App\Models\Page;
use App\Models\Service;
use App\Services\CityService;
use App\Services\SiteSearchService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntitySearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(CityService::class)->setCurrentCity(null);
    }

    public function test_search_finds_a_public_doctor_by_surname_full_name_and_speciality_outside_page_blocks(): void
    {
        $doctor = $this->createDoctor([
            'name' => 'Анна',
            'surname' => 'Смирнова',
            'speciality' => 'Лазерный офтальмохирург',
            'job_title' => 'Врач высшей категории',
            'excerpt' => 'Проводит лазерную коррекцию зрения.',
            'bio' => 'Биография врача.',
        ]);

        $this->assertSame(["doctor:{$doctor->id}"], app(SiteSearchService::class)->suggest('Смирнова Анна', 10)->pluck('key')->all());
        $this->assertSame(["doctor:{$doctor->id}"], app(SiteSearchService::class)->suggest('офтальмохирург', 10)->pluck('key')->all());
    }

    public function test_search_excludes_noindex_doctors_and_keeps_the_city_scoped_profile_url(): void
    {
        $city = $this->createCity('Киров', 'kirov');
        $foreignCity = $this->createCity('Пермь', 'perm');
        app(CityService::class)->setCurrentCity($city);

        $visible = $this->createDoctor(['surname' => 'Кировский', 'handle' => 'kirovskiy']);
        $noindex = $this->createDoctor(['surname' => 'Скрытый', 'seo' => ['noindex' => true]]);
        $foreign = $this->createDoctor(['surname' => 'Пермский']);
        $visibleService = $this->createService(['title' => 'Кировская услуга']);
        $foreignService = $this->createService(['title' => 'Пермская услуга']);
        $visible->cities()->attach($city);
        $noindex->cities()->attach($city);
        $foreign->cities()->attach($foreignCity);
        $visibleService->cities()->attach($city);
        $foreignService->cities()->attach($foreignCity);

        Doctor::addGlobalScope('test-production-city', function (Builder $query) use ($city): void {
            $query->whereExists(function ($subQuery) use ($city): void {
                $subQuery->selectRaw(1)
                    ->from('city_doctor')
                    ->whereColumn('city_doctor.doctor_id', 'doctors.id')
                    ->where('city_doctor.city_id', $city->id);
            });
        });
        Service::addGlobalScope('test-production-city', function (Builder $query) use ($city): void {
            $query->whereExists(function ($subQuery) use ($city): void {
                $subQuery->selectRaw(1)
                    ->from('city_service')
                    ->whereColumn('city_service.service_id', 'services.id')
                    ->where('city_service.city_id', $city->id);
            });
        });

        try {
            $results = app(SiteSearchService::class)->suggest('Кировский', 10);

            $this->assertSame(["doctor:{$visible->id}"], $results->pluck('key')->all());
            $this->assertStringEndsWith('/kirov/doctors/kirovskiy', $results->first()->url);
            $this->assertNotContains("doctor:{$noindex->id}", app(SiteSearchService::class)->suggest('Скрытый', 10)->pluck('key')->all());
            $this->assertNotContains("doctor:{$foreign->id}", app(SiteSearchService::class)->suggest('Пермский', 10)->pluck('key')->all());
            $serviceResults = app(SiteSearchService::class)->suggest('Кировская услуга', 10);
            $this->assertSame(["service:{$visibleService->id}"], $serviceResults->pluck('key')->all());
            $this->assertStringEndsWith("/kirov/services#{$visibleService->uuid}", $serviceResults->first()->url);
            $this->assertNotContains("service:{$foreignService->id}", app(SiteSearchService::class)->suggest('Пермская услуга', 10)->pluck('key')->all());
        } finally {
            Doctor::clearBootedModels();
            Service::clearBootedModels();
        }
    }

    public function test_search_finds_active_parent_and_child_services_with_their_parent_anchor(): void
    {
        $parent = $this->createService(['title' => 'Лазерная коррекция']);
        $child = $this->createService(['title' => 'Femto LASIK', 'parent_id' => $parent->id]);
        $inactive = $this->createService(['title' => 'Лазерная коррекция скрытая', 'is_active' => false]);

        $parentMatches = app(SiteSearchService::class)->suggest('лазерная коррекция', 10);
        $parentResult = $parentMatches->firstWhere('key', "service:{$parent->id}");
        $childByParentResult = $parentMatches->firstWhere('key', "service:{$child->id}");
        $childResult = app(SiteSearchService::class)->suggest('femto lasik', 10)->firstWhere('key', "service:{$child->id}");

        $this->assertNotNull($parentResult);
        $this->assertSame(url('/services') . "#{$parent->uuid}", $parentResult->url);
        $this->assertNotNull($childByParentResult);
        $this->assertNotNull($childResult);
        $this->assertSame(url('/services') . "#{$parent->uuid}", $childResult->url);
        $this->assertStringContainsString('Лазерная коррекция', (string) $childResult->snippet);
        $this->assertNotContains("service:{$inactive->id}", app(SiteSearchService::class)->suggest('скрытая', 10)->pluck('key')->all());
    }

    public function test_direct_entity_title_matches_rank_above_page_body_mentions_and_keep_unique_type_keys(): void
    {
        $doctor = $this->createDoctor(['surname' => 'Диагностика', 'name' => 'Иван']);
        $service = $this->createService(['title' => 'Диагностика']);
        $page = Page::create([
            'title' => 'Информация',
            'handle' => 'information-' . uniqid(),
            'active' => true,
            'body_html' => 'диагностика зрения.',
        ]);

        $results = app(SiteSearchService::class)->suggest('диагностика', 10);

        $this->assertSame(["service:{$service->id}", "doctor:{$doctor->id}", "page:{$page->id}"], $results->pluck('key')->all());
        $this->assertSame(3, $results->pluck('key')->unique()->count());
    }

    public function test_search_paginates_the_combined_entity_and_page_result_set_with_its_total(): void
    {
        $doctor = $this->createDoctor(['surname' => 'Диагностика']);
        $service = $this->createService(['title' => 'Диагностика услуги']);
        $page = Page::create([
            'title' => 'диагностика страницы',
            'handle' => 'diagnostics-' . uniqid(),
            'active' => true,
        ]);

        $paginator = app(SiteSearchService::class)->search('диагностика', perPage: 2, page: 2);

        $this->assertSame(3, $paginator->total());
        $this->assertSame(2, $paginator->currentPage());
        $this->assertSame("page:{$page->id}", $paginator->first()->key);
        $this->assertStringContainsString('q=%D0%B4%D0%B8%D0%B0%D0%B3%D0%BD%D0%BE%D1%81%D1%82%D0%B8%D0%BA%D0%B0', $paginator->url(1));
    }

    private function createCity(string $name, string $slug): City
    {
        return City::create([
            'name' => $name,
            'slug' => $slug,
            'is_default' => false,
            'active' => true,
        ]);
    }

    private function createDoctor(array $attributes = []): Doctor
    {
        return Doctor::create(array_replace([
            'name' => 'Иван',
            'surname' => 'Врач',
            'speciality' => 'Офтальмолог',
            'job_title' => 'Врач',
            'excerpt' => null,
            'bio' => 'Биография',
            'handle' => 'doctor-' . uniqid(),
            'seo' => null,
        ], $attributes));
    }

    private function createService(array $attributes = []): Service
    {
        return Service::create(array_replace([
            'title' => 'Услуга',
            'uuid' => (string) fake()->uuid(),
            'parent_id' => null,
            'is_active' => true,
            'sort_order' => 0,
        ], $attributes));
    }
}
