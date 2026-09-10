<?php

namespace Tests\Feature\Blocks;

use App\Enums\BlockType;
use App\Enums\PageType;
use App\Models\Block;
use App\Models\City;
use App\Models\Page;
use App\Services\CityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BlogSliderBlockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Queue::fake();
    }

    public function test_it_returns_only_the_twelve_newest_active_posts(): void
    {
        foreach (range(1, 14) as $day) {
            $this->createPage(
                handle: "post-{$day}",
                createdAt: sprintf('2026-08-%02d 10:00:00', $day),
            );
        }

        $this->createPage(
            handle: 'inactive-newer-post',
            createdAt: '2026-09-01 10:00:00',
            active: false,
        );
        $this->createPage(
            handle: 'newer-non-post-page',
            createdAt: '2026-09-02 10:00:00',
            type: PageType::Default,
        );

        $posts = $this->blogSlider()->posts;

        $this->assertCount(12, $posts);
        $this->assertSame(
            array_map(fn (int $day): string => "post-{$day}", range(14, 3)),
            $posts->pluck('handle')->all(),
        );
        $this->assertTrue($posts->every(
            fn (Page $post): bool => $post->relationLoaded('tags')
                && $post->relationLoaded('media')
                && $post->relationLoaded('category'),
        ));
    }

    public function test_it_uses_the_newest_id_first_when_posts_share_a_timestamp(): void
    {
        $olderId = $this->createPage('older-id', '2026-08-01 10:00:00');
        $newerId = $this->createPage('newer-id', '2026-08-01 10:00:00');

        $this->assertSame(
            [$newerId->id, $olderId->id],
            $this->blogSlider()->posts->pluck('id')->all(),
        );
    }

    public function test_it_uses_the_same_global_cached_results_for_every_city(): void
    {
        $moscow = $this->createCity('Москва', 'moskva', true);
        $spb = $this->createCity('Санкт-Петербург', 'spb');
        $cityService = app(CityService::class);

        $this->createPage('original-post', '2026-08-01 10:00:00');

        $cityService->setCurrentCity($moscow);
        $moscowPosts = $this->blogSlider()->posts;

        Page::withoutEvents(fn (): Page => $this->createPage('new-post', '2026-08-02 10:00:00'));

        $cityService->setCurrentCity($spb);
        $spbPosts = $this->blogSlider()->posts;

        $this->assertSame(['original-post'], $moscowPosts->pluck('handle')->all());
        $this->assertSame(['original-post'], $spbPosts->pluck('handle')->all());
    }

    public function test_it_ignores_the_city_scope_for_global_articles(): void
    {
        $this->createPage('global-post', '2026-08-01 10:00:00');
        Page::addGlobalScope('city', fn (Builder $query): Builder => $query->whereRaw('1 = 0'));
        Cache::flush();

        try {
            $this->assertSame(
                ['global-post'],
                $this->blogSlider()->posts->pluck('handle')->all(),
            );
        } finally {
            Page::clearBootedModels();
        }
    }

    public function test_it_preserves_the_all_articles_link(): void
    {
        $html = view('components.block.blog-slider', [
            'block' => $this->blogSlider(),
        ])->render();

        $this->assertStringContainsString('href="'.route('stati.index').'"', $html);
        $this->assertStringContainsString('>Все статьи</a>', $html);
    }

    private function blogSlider(): Block
    {
        return new Block([
            'type' => BlockType::CARDS_SLIDER,
            'title' => 'Статьи',
            'settings' => ['title_hidden' => false],
            'payload' => [
                'is_blog' => true,
                'count_visible' => 3,
            ],
        ]);
    }

    private function createPage(
        string $handle,
        string $createdAt,
        bool $active = true,
        PageType $type = PageType::Posts,
    ): Page {
        $page = Page::query()->create([
            'title' => ucfirst($handle),
            'handle' => $handle,
            'type' => $type,
            'active' => $active,
        ]);

        $page->timestamps = false;
        $page->created_at = $createdAt;
        $page->updated_at = $createdAt;
        $page->saveQuietly();
        $page->timestamps = true;

        return $page;
    }

    private function createCity(string $name, string $slug, bool $isDefault = false): City
    {
        return City::query()->create([
            'name' => $name,
            'slug' => $slug,
            'is_default' => $isDefault,
            'active' => true,
            'details' => [],
        ]);
    }
}
