<?php

namespace Tests\Unit\Services;

use App\Enums\PageType;
use App\Models\City;
use App\Models\Page;
use App\Services\CityService;
use App\Services\PageService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PageServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    /** @test */
    public function it_forgets_only_related_post_caches_without_flushing_everything(): void
    {
        $this->mock(CityService::class, function ($mock): void {
            $mock->shouldReceive('getActiveCities')
                ->andReturn(new Collection([
                    new City(['slug' => 'moscow']),
                    new City(['slug' => 'spb']),
                ]));
        });

        Cache::put('page-moscow-reviews', true, 60);
        Cache::put('page-spb-reviews', true, 60);
        Cache::put('page-global-reviews', true, 60);
        Cache::put('page-reviews', true, 60);
        Cache::put('page_reviews_index', true, 60);
        Cache::put('posts_filter', true, 60);
        Cache::put('blog_posts_for_slider', true, 60);
        Cache::put('unrelated-cache-key', true, 60);

        $page = new Page([
            'handle' => 'reviews',
            'type' => PageType::Posts,
        ]);
        $page->syncOriginal();

        app(PageService::class)->clearPageCache($page);

        $this->assertFalse(Cache::has('page-moscow-reviews'));
        $this->assertFalse(Cache::has('page-spb-reviews'));
        $this->assertFalse(Cache::has('page-global-reviews'));
        $this->assertFalse(Cache::has('page-reviews'));
        $this->assertFalse(Cache::has('page_reviews_index'));
        $this->assertFalse(Cache::has('posts_filter'));
        $this->assertFalse(Cache::has('blog_posts_for_slider'));
        $this->assertTrue(Cache::has('unrelated-cache-key'));
    }

    /** @test */
    public function it_clears_doctors_pagination_cache_for_doctors_pages(): void
    {
        $this->mock(CityService::class, function ($mock): void {
            $mock->shouldReceive('getActiveCities')
                ->andReturn(new Collection([
                    new City(['slug' => 'moscow']),
                ]));
        });

        Cache::put('doctors-page-moscow-1', true, 60);
        Cache::put('doctors-page-global-1', true, 60);
        Cache::put('unrelated-cache-key', true, 60);

        $page = new Page([
            'handle' => 'doctors',
            'type' => PageType::Doctors,
        ]);
        $page->syncOriginal();

        app(PageService::class)->clearPageCache($page);

        $this->assertFalse(Cache::has('doctors-page-moscow-1'));
        $this->assertFalse(Cache::has('doctors-page-global-1'));
        $this->assertTrue(Cache::has('unrelated-cache-key'));
    }
}
