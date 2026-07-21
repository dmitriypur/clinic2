<?php

namespace Tests\Feature\SiteSearch;

use App\Models\City;
use App\Models\Page;
use App\Models\SiteSearchQuery;
use App\Services\CityService;
use App\Services\SiteSearchAnalyticsRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SearchAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(CityService::class)->setCurrentCity(null);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_full_search_records_the_normalized_query_current_city_and_total(): void
    {
        $city = $this->createCity();
        $this->createPage(['title' => 'лазерная коррекция зрения']);

        $this->get('/search?q=%20%20лазерная%20%20коррекция%20%20')
            ->assertOk();

        $this->assertDatabaseHas('site_search_queries', [
            'query' => 'лазерная коррекция',
            'city_id' => $city->id,
            'results_count' => 1,
        ]);
    }

    public function test_live_search_never_records_analytics(): void
    {
        $this->createPage(['title' => 'лазерная коррекция зрения']);

        $this->getJson('/live-search?query=лазерная')
            ->assertOk();

        $this->assertDatabaseCount('site_search_queries', 0);
    }

    public function test_search_queries_with_emails_or_phone_like_runs_are_not_recorded(): void
    {
        $this->get('/search?q=doctor%40example.test')
            ->assertOk();
        $this->get('/search?q=%2B7%20%28999%29%20123-45-67')
            ->assertOk();

        $this->assertDatabaseCount('site_search_queries', 0);
    }

    public function test_search_queries_with_unicode_emails_are_not_recorded(): void
    {
        $this->get('/search?q=тест%40пример.рф')
            ->assertOk();

        $this->assertDatabaseCount('site_search_queries', 0);
    }

    public function test_search_results_are_returned_when_analytics_storage_fails(): void
    {
        $this->createPage(['title' => 'лазерная коррекция зрения']);

        $recorder = $this->mock(SiteSearchAnalyticsRecorder::class);
        $recorder->shouldReceive('record')
            ->once()
            ->andThrow(new \RuntimeException('Analytics storage is unavailable.'));

        $this->get('/search?q=лазерная')
            ->assertOk()
            ->assertSee('лазерная коррекция зрения');
    }

    public function test_prunable_query_contains_only_records_older_than_ninety_days(): void
    {
        Carbon::setTestNow('2026-07-21 12:00:00');

        $expired = SiteSearchQuery::forceCreate([
            'query' => 'устаревший запрос',
            'results_count' => 0,
            'created_at' => now()->subDays(90)->subSecond(),
            'updated_at' => now()->subDays(90)->subSecond(),
        ]);
        $boundary = SiteSearchQuery::forceCreate([
            'query' => 'граничный запрос',
            'results_count' => 0,
            'created_at' => now()->subDays(90),
            'updated_at' => now()->subDays(90),
        ]);
        $recent = SiteSearchQuery::forceCreate([
            'query' => 'свежий запрос',
            'results_count' => 0,
            'created_at' => now()->subDays(89),
            'updated_at' => now()->subDays(89),
        ]);

        $this->assertSame([$expired->id], (new SiteSearchQuery())->prunable()->pluck('id')->all());
        $this->assertNotContains($boundary->id, (new SiteSearchQuery())->prunable()->pluck('id')->all());
        $this->assertNotContains($recent->id, (new SiteSearchQuery())->prunable()->pluck('id')->all());
    }

    private function createCity(): City
    {
        return City::create([
            'name' => 'Киров',
            'slug' => 'kirov',
            'is_default' => true,
            'active' => true,
        ]);
    }

    private function createPage(array $attributes = []): Page
    {
        return Page::create(array_replace([
            'title' => 'Страница',
            'handle' => 'page-' . uniqid(),
            'active' => true,
            'body_html' => null,
        ], $attributes));
    }
}
