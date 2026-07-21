<?php

namespace Tests\Feature\SiteSearch;

use App\Enums\BlockType;
use App\Models\Block;
use App\Models\Category;
use App\Models\City;
use App\Models\Page;
use App\Services\CityService;
use App\Services\SiteSearchService;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class PageSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(CityService::class)->setCurrentCity(null);
    }

    public function test_live_search_exposes_the_shared_page_result_contract(): void
    {
        $page = $this->createPage([
            'title' => 'лазерная коррекция зрения',
            'body_html' => '<p>Безопасное лечение.</p>',
        ]);

        $this->getJson('/live-search?query=лазерная')
            ->assertOk()
            ->assertJsonPath('0.id', $page->id)
            ->assertJsonPath('0.key', "page:{$page->id}")
            ->assertJsonPath('0.type', 'page')
            ->assertJsonPath('0.type_label', 'Страница')
            ->assertJsonPath('0.title', 'лазерная коррекция зрения')
            ->assertJsonPath('0.handle', $page->getUrl());
    }

    public function test_search_excludes_inactive_pages_even_when_their_body_or_block_matches(): void
    {
        $active = $this->createPage(['title' => 'активная диагностика']);
        $inactiveBody = $this->createPage([
            'title' => 'скрытая страница',
            'active' => false,
            'body_html' => 'секретная диагностика',
        ]);
        $inactiveBlock = $this->createPage(['title' => 'скрытый блок', 'active' => false]);
        $this->createBlock($inactiveBlock, ['body_html' => 'секретная диагностика']);

        $results = app(SiteSearchService::class)->suggest('диагностика', 10);

        $this->assertSame(["page:{$active->id}"], $results->pluck('key')->all());
        $this->assertNotContains("page:{$inactiveBody->id}", $results->pluck('key')->all());
        $this->assertNotContains("page:{$inactiveBlock->id}", $results->pluck('key')->all());
    }

    public function test_search_treats_like_wildcards_as_literal_characters(): void
    {
        $literal = $this->createPage(['title' => 'цена 100%_гарантия']);
        $nearMatch = $this->createPage(['title' => 'цена 100abcгарантия']);

        $results = app(SiteSearchService::class)->suggest('100%_гарантия', 10);

        $this->assertSame(["page:{$literal->id}"], $results->pluck('key')->all());
        $this->assertNotContains("page:{$nearMatch->id}", $results->pluck('key')->all());
    }

    public function test_search_requires_every_token_but_allows_non_contiguous_matches_in_one_page(): void
    {
        $matching = $this->createPage(['title' => 'лазерная методика', 'body_html' => 'точная коррекция зрения']);
        $missing = $this->createPage(['title' => 'лазерная методика', 'body_html' => 'точное лечение']);

        $results = app(SiteSearchService::class)->suggest('лазерная коррекция', 10);

        $this->assertSame(["page:{$matching->id}"], $results->pluck('key')->all());
        $this->assertNotContains("page:{$missing->id}", $results->pluck('key')->all());
    }

    public function test_search_returns_one_page_and_a_safe_snippet_for_multiple_matching_blocks(): void
    {
        $page = $this->createPage(['title' => 'страница с блоками']);
        $this->createBlock($page, ['body_html' => '<p>Первый <strong>лазерный</strong> блок.</p>']);
        $this->createBlock($page, [
            'body_html' => null,
            'payload' => ['copy' => '<script>alert(1)</script> лазерная коррекция'],
        ]);

        $results = app(SiteSearchService::class)->suggest('лазерная', 10);

        $this->assertCount(1, $results);
        $this->assertSame("page:{$page->id}", $results->first()->key);
        $this->assertStringNotContainsString('<', (string) $results->first()->snippet);
        $this->assertStringContainsString('лазерная коррекция', (string) $results->first()->snippet);
    }

    public function test_search_prefers_a_matching_block_when_the_page_body_does_not_match(): void
    {
        $page = $this->createPage([
            'title' => 'Информационная страница',
            'body_html' => '<p>Общие сведения о клинике.</p>',
        ]);
        $this->createBlock($page, ['body_html' => '<p>Точная лазерная диагностика зрения.</p>']);

        $result = app(SiteSearchService::class)->suggest('лазерная диагностика', 10)->first();

        $this->assertSame("page:{$page->id}", $result?->key);
        $this->assertStringContainsString('лазерная диагностика', (string) $result?->snippet);
        $this->assertStringNotContainsString('Общие сведения', (string) $result?->snippet);
    }

    public function test_search_decodes_entities_before_removing_encoded_markup_from_snippets(): void
    {
        $page = $this->createPage([
            'title' => 'Информационная страница',
            'body_html' => '&lt;script&gt;alert(1)&lt;/script&gt; безопасная диагностика',
        ]);

        $result = app(SiteSearchService::class)->suggest('безопасная диагностика', 10)->first();

        $this->assertSame("page:{$page->id}", $result?->key);
        $this->assertSame('alert(1) безопасная диагностика', $result?->snippet);
        $this->assertStringNotContainsString('<script', (string) $result?->snippet);
    }

    public function test_page_category_urls_are_eager_loaded_without_result_count_dependent_queries(): void
    {
        $singleCategory = Category::create(['title' => 'Одна категория', 'handle' => 'single-category']);
        $manyCategory = Category::create(['title' => 'Несколько страниц', 'handle' => 'many-category']);
        $single = $this->createPage(['title' => 'единичный результат', 'category_id' => $singleCategory->id]);
        $many = collect(range(1, 3))->map(fn (int $number): Page => $this->createPage([
            'title' => "массовый результат {$number}",
            'category_id' => $manyCategory->id,
        ]));

        DB::enableQueryLog();
        DB::flushQueryLog();
        $singleResults = app(SiteSearchService::class)->suggest('единичный результат', 10);
        $singleCategoryQueryCount = collect(DB::getQueryLog())
            ->filter(fn (array $query): bool => str_contains($query['query'], 'from "categories"'))
            ->count();

        DB::flushQueryLog();
        $manyResults = app(SiteSearchService::class)->suggest('массовый результат', 10);
        $manyCategoryQueryCount = collect(DB::getQueryLog())
            ->filter(fn (array $query): bool => str_contains($query['query'], 'from "categories"'))
            ->count();
        DB::disableQueryLog();

        $this->assertSame(["page:{$single->id}"], $singleResults->pluck('key')->all());
        $this->assertSame($many->map(fn (Page $page): string => "page:{$page->id}")->all(), $manyResults->pluck('key')->all());
        $this->assertStringContainsString('/single-category/', $singleResults->first()->url);
        $this->assertSame(1, $singleCategoryQueryCount);
        $this->assertSame($singleCategoryQueryCount, $manyCategoryQueryCount, 'Page result count must not add category queries.');
    }

    public function test_mysql_payload_search_applies_a_case_insensitive_collation_to_the_search_pattern(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('getDriverName')->twice()->andReturn('mysql');

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('getConnection')->twice()->andReturn($connection);
        $builder->shouldReceive('orWhereRaw')->once()->with(
            "JSON_SEARCH(payload, 'one', CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci, '!') IS NOT NULL",
            ['%лазерная%'],
        );

        $method = new \ReflectionMethod(SiteSearchService::class, 'wherePayloadContains');
        $method->invoke(app(SiteSearchService::class), $builder, '%лазерная%');

        $this->addToAssertionCount(1);
    }

    public function test_search_ranks_title_matches_before_supporting_text_with_stable_ties(): void
    {
        $exact = $this->createPage(['title' => 'лазерная коррекция']);
        $prefix = $this->createPage(['title' => 'лазерная коррекция зрения']);
        $phrase = $this->createPage(['title' => 'современная лазерная коррекция']);
        $allTitle = $this->createPage(['title' => 'лазерная безопасная коррекция']);
        $supporting = $this->createPage(['title' => 'а страница', 'body_html' => 'лазерная коррекция']);

        $results = app(SiteSearchService::class)->suggest('лазерная коррекция', 10);

        $this->assertSame([
            "page:{$exact->id}",
            "page:{$prefix->id}",
            "page:{$phrase->id}",
            "page:{$allTitle->id}",
            "page:{$supporting->id}",
        ], $results->pluck('key')->all());
    }

    public function test_search_ranks_the_effective_phrase_after_ignoring_the_current_city(): void
    {
        $city = City::create([
            'name' => 'Киров',
            'slug' => 'kirov',
            'is_default' => false,
            'active' => true,
        ]);
        app(CityService::class)->setCurrentCity($city);

        $exact = $this->createPage(['title' => 'лазерная коррекция']);
        $allTokens = $this->createPage(['title' => 'абажур лазерная современная коррекция']);

        $results = app(SiteSearchService::class)->suggest('Киров лазерная коррекция', 10);

        $this->assertSame([
            "page:{$exact->id}",
            "page:{$allTokens->id}",
        ], $results->pluck('key')->all());
    }

    public function test_live_search_honors_a_production_like_city_global_scope(): void
    {
        $currentCity = City::create([
            'name' => 'Киров',
            'slug' => 'kirov',
            'is_default' => false,
            'active' => true,
        ]);
        $foreignCity = City::create([
            'name' => 'Пермь',
            'slug' => 'perm',
            'is_default' => false,
            'active' => true,
        ]);
        app(CityService::class)->setCurrentCity($currentCity);

        $currentPage = $this->createPage(['title' => 'лазерная коррекция в Кирове']);
        $foreignPage = $this->createPage(['title' => 'лазерная коррекция в Перми']);
        $currentPage->cities()->attach($currentCity);
        $foreignPage->cities()->attach($foreignCity);

        Page::addGlobalScope('test-production-city', function (Builder $query) use ($currentCity): void {
            $query->whereExists(function ($subQuery) use ($currentCity): void {
                $subQuery->selectRaw(1)
                    ->from('city_page')
                    ->whereColumn('city_page.page_id', 'pages.id')
                    ->where('city_page.city_id', $currentCity->id);
            });
        });

        try {
            $results = $this->getJson('/live-search?query=лазерная коррекция')
                ->assertOk()
                ->json();

            $this->assertSame([$currentPage->id], collect($results)->pluck('id')->all());
            $this->assertNotContains($foreignPage->id, collect($results)->pluck('id')->all());
        } finally {
            Page::clearBootedModels();
        }
    }

    public function test_search_uses_current_city_for_urls_and_resolves_seo_variables_before_display(): void
    {
        $city = City::create([
            'name' => 'Киров',
            'slug' => 'kirov',
            'is_default' => false,
            'active' => true,
            'seo_cases' => ['prepositional' => 'Кирове'],
        ]);
        app(CityService::class)->setCurrentCity($city);

        $page = $this->createPage([
            'title' => 'лазерная диагностика в {city}',
            'body_html' => '<p>Современная диагностика.</p>',
        ]);
        $page->cities()->attach($city);

        $result = app(SiteSearchService::class)->suggest('лазерная', 10)->first();

        $this->assertSame('лазерная диагностика в Киров', $result->title);
        $this->assertStringEndsWith('/kirov/' . $page->handle, $result->url);
    }

    public function test_short_city_only_and_phone_only_terms_return_without_page_queries(): void
    {
        $city = City::create([
            'name' => 'Киров',
            'slug' => 'kirov',
            'is_default' => false,
            'active' => true,
        ]);
        app(CityService::class)->setCurrentCity($city);

        DB::flushQueryLog();
        DB::enableQueryLog();
        app(SiteSearchService::class)->suggest('Киров');
        app(SiteSearchService::class)->suggest('+7 (999) 123-45-67');
        app(SiteSearchService::class)->suggest('я');
        app(SiteSearchService::class)->suggest('я +7 (999) 123-45-67');

        $this->assertSame([], DB::getQueryLog());
    }

    public function test_full_search_preserves_normalized_query_in_pagination_and_live_is_its_prefix(): void
    {
        foreach (range(1, 31) as $number) {
            $this->createPage([
                'title' => sprintf('лазерная коррекция %02d', $number),
                'handle' => sprintf('laser-%02d', $number),
            ]);
        }

        $full = $this->get('/search?q=%20%20лазерная%20%20коррекция%20%20')
            ->assertOk()
            ->viewData('results');
        $live = $this->getJson('/live-search?query=%20%20лазерная%20%20коррекция%20%20')
            ->assertOk()
            ->json();

        $this->assertStringContainsString('q=%D0%BB%D0%B0%D0%B7%D0%B5%D1%80%D0%BD%D0%B0%D1%8F%20%D0%BA%D0%BE%D1%80%D1%80%D0%B5%D0%BA%D1%86%D0%B8%D1%8F', $full->url(2));
        $this->assertSame(
            $full->getCollection()->take(5)->pluck('key')->all(),
            collect($live)->pluck('key')->all(),
        );
    }

    public function test_search_validates_the_endpoint_specific_input_and_maximum_length(): void
    {
        $this->getJson('/live-search?q=лазерная')
            ->assertOk()
            ->assertExactJson([]);

        $this->getJson('/live-search?query=' . str_repeat('а', 101))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('query');
    }

    public function test_city_prefixed_search_uses_city_aware_actions_live_endpoint_and_validation_redirect(): void
    {
        $city = City::create([
            'name' => 'Санкт-Петербург',
            'slug' => 'spb',
            'is_default' => false,
            'active' => true,
        ]);
        $invalidQuery = str_repeat('а', 101);

        $this->get('/spb/search')
            ->assertOk()
            ->assertSee('action="' . url('/spb/search') . '"', false);

        $this->getJson('/spb/live-search?query=лазерная')
            ->assertOk();

        $this->from('/somewhere')
            ->get('/spb/search?q=' . $invalidQuery)
            ->assertRedirect(url('/spb/search'))
            ->assertSessionHasErrors('q')
            ->assertSessionHasInput('q', $invalidQuery);

        $this->get('/spb/search')
            ->assertOk()
            ->assertSee('value="' . $invalidQuery . '"', false);
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

    private function createBlock(Page $page, array $attributes = []): Block
    {
        return Block::create(array_replace([
            'page_id' => $page->id,
            'type' => BlockType::HTML,
            'title' => 'Блок',
            'body_html' => null,
            'payload' => null,
            'order_column' => Block::query()->where('page_id', $page->id)->count(),
        ], $attributes));
    }
}
