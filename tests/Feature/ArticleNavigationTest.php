<?php

namespace Tests\Feature;

use App\Contracts\ArticleSortStrategy;
use App\Enums\BlockType;
use App\Enums\PageType;
use App\Filament\Actions\Tables\ReplicateBlockAction;
use App\Models\Block;
use App\Models\Category;
use App\Models\Page;
use App\Models\Tag;
use App\Services\ArticleOrderingService;
use App\Services\ArticleNavigationBlockService;
use App\Services\ArticleSorting\NewestArticleSortStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ArticleNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_article_sort_strategy_is_resolved_from_the_container(): void
    {
        $this->assertInstanceOf(
            NewestArticleSortStrategy::class,
            app(ArticleSortStrategy::class),
        );
    }

    public function test_article_order_and_neighbors_match_the_cards_order(): void
    {
        $category = $this->createCategory('stati');
        $otherCategory = $this->createCategory('directory');

        $first = $this->createPage($category, 'first', '2026-06-03 10:00:00');
        $second = $this->createPage($category, 'second', '2026-06-02 10:00:00');
        $third = $this->createPage($category, 'third', '2026-06-01 10:00:00');

        $this->createPage($category, 'inactive', '2026-06-04 10:00:00', ['active' => false]);
        $this->createPage($otherCategory, 'other-category', '2026-06-04 10:00:00');
        $this->createPage($category, 'blog', '2026-06-04 10:00:00', ['type' => PageType::Blog]);

        $service = app(ArticleOrderingService::class);
        $orderedHandles = $service
            ->apply(Page::query()->where('category_id', $category->id)->where('active', true))
            ->pluck('handle')
            ->all();

        $this->assertSame(['first', 'second', 'third'], $orderedHandles);

        $firstNeighbors = $service->neighbors($first);
        $this->assertNull($firstNeighbors->previous);
        $this->assertSame($second->id, $firstNeighbors->next?->id);

        $secondNeighbors = $service->neighbors($second);
        $this->assertSame($first->id, $secondNeighbors->previous?->id);
        $this->assertSame($third->id, $secondNeighbors->next?->id);

        $thirdNeighbors = $service->neighbors($third);
        $this->assertSame($second->id, $thirdNeighbors->previous?->id);
        $this->assertNull($thirdNeighbors->next);
    }

    public function test_article_order_is_stable_when_created_dates_are_equal(): void
    {
        $category = $this->createCategory('stati');
        $olderId = $this->createPage($category, 'older-id', '2026-06-01 10:00:00');
        $newerId = $this->createPage($category, 'newer-id', '2026-06-01 10:00:00');

        $service = app(ArticleOrderingService::class);

        $this->assertSame(
            [$newerId->id, $olderId->id],
            $service
                ->apply(Page::query()->where('category_id', $category->id)->where('active', true))
                ->pluck('id')
                ->all(),
        );

        $neighbors = $service->neighbors($newerId);
        $this->assertNull($neighbors->previous);
        $this->assertSame($olderId->id, $neighbors->next?->id);
    }

    public function test_article_ordering_accepts_tag_pages_relation_and_keeps_tag_filter(): void
    {
        $category = $this->createCategory('stati');
        $tag = Tag::query()->create([
            'title' => 'Близорукость',
            'handle' => 'blizorukost',
        ]);
        $taggedArticle = $this->createPage($category, 'tagged', '2026-06-02 10:00:00');
        $untaggedArticle = $this->createPage($category, 'untagged', '2026-06-03 10:00:00');
        $taggedArticle->tags()->attach($tag);

        $articles = app(ArticleOrderingService::class)
            ->apply($tag->pages())
            ->get();

        $this->assertSame([$taggedArticle->id], $articles->pluck('id')->all());
        $this->assertFalse($articles->contains($untaggedArticle));
    }

    public function test_navigation_block_renders_only_for_post_pages(): void
    {
        $category = $this->createCategory('stati');
        $article = $this->createPage($category, 'article', '2026-06-01 10:00:00');
        $blog = $this->createPage($category, 'blog-page', '2026-06-01 10:00:00', ['type' => PageType::Blog]);
        $block = new Block([
            'type' => BlockType::ARTICLE_NAVIGATION,
            'title' => 'Навигация по статьям',
            'settings' => [],
            'payload' => [],
        ]);

        $articleHtml = Blade::render(
            '<x-block :block="$block" :page="$page" breadcrumbs-title="" page-title="" page-description="" />',
            ['block' => $block, 'page' => $article],
        );
        $blogHtml = Blade::render(
            '<x-block :block="$block" :page="$page" breadcrumbs-title="" page-title="" page-description="" />',
            ['block' => $block, 'page' => $blog],
        );

        $this->assertStringContainsString('article-navigation', $articleHtml);
        $this->assertSame('', trim($blogHtml));
    }

    public function test_navigation_block_uses_adjacent_article_urls_and_hides_missing_direction(): void
    {
        $category = $this->createCategory('stati');
        $first = $this->createPage($category, 'first', '2026-06-02 10:00:00');
        $second = $this->createPage($category, 'second', '2026-06-01 10:00:00');
        $block = new Block([
            'type' => BlockType::ARTICLE_NAVIGATION,
            'title' => 'Навигация по статьям',
            'settings' => [],
            'payload' => [],
        ]);

        $firstHtml = Blade::render(
            '<x-block :block="$block" :page="$page" breadcrumbs-title="" page-title="" page-description="" />',
            ['block' => $block, 'page' => $first],
        );
        $secondHtml = Blade::render(
            '<x-block :block="$block" :page="$page" breadcrumbs-title="" page-title="" page-description="" />',
            ['block' => $block, 'page' => $second],
        );

        $this->assertStringNotContainsString('Предыдущая статья', $firstHtml);
        $this->assertStringContainsString('href="' . $second->getUrl() . '"', $firstHtml);
        $this->assertStringContainsString('Следующая статья', $firstHtml);

        $this->assertStringContainsString('href="' . $first->getUrl() . '"', $secondHtml);
        $this->assertStringContainsString('Предыдущая статья', $secondHtml);
        $this->assertStringNotContainsString('Следующая статья', $secondHtml);
    }

    public function test_navigation_block_can_only_be_saved_once_on_a_post_page(): void
    {
        $category = $this->createCategory('stati');
        $article = $this->createPage($category, 'article', '2026-06-01 10:00:00');
        $blog = $this->createPage($category, 'blog', '2026-06-01 10:00:00', ['type' => PageType::Blog]);

        try {
            Block::query()->create([
                'page_id' => $article->id,
                'type' => BlockType::ARTICLE_NAVIGATION,
                'title' => 'Повторная навигация',
            ]);
            $this->fail('Duplicate navigation block was saved.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('type', $exception->errors());
        }

        $this->expectException(ValidationException::class);

        Block::query()->create([
            'page_id' => $blog->id,
            'type' => BlockType::ARTICLE_NAVIGATION,
            'title' => 'Навигация блога',
        ]);
    }

    public function test_navigation_block_can_be_deleted_and_added_manually_again(): void
    {
        $category = $this->createCategory('stati');
        $article = $this->createPage($category, 'article', '2026-06-01 10:00:00');

        $article->blocks()
            ->where('type', BlockType::ARTICLE_NAVIGATION)
            ->firstOrFail()
            ->delete();

        $this->assertSame(0, $article->blocks()->where('type', BlockType::ARTICLE_NAVIGATION)->count());

        Block::query()->create([
            'page_id' => $article->id,
            'type' => BlockType::ARTICLE_NAVIGATION,
            'title' => 'Навигация по статьям',
        ]);

        $this->assertSame(1, $article->blocks()->where('type', BlockType::ARTICLE_NAVIGATION)->count());
    }

    public function test_block_events_still_clear_the_shared_services_cache(): void
    {
        $category = $this->createCategory('stati');
        $article = $this->createPage($category, 'article', '2026-06-01 10:00:00');
        Cache::put('services_with_media_and_prices', ['cached']);

        Block::query()->create([
            'page_id' => $article->id,
            'type' => BlockType::POST_TEXT,
            'title' => 'Текст статьи',
        ]);

        $this->assertFalse(Cache::has('services_with_media_and_prices'));
    }

    public function test_navigation_block_cannot_be_replicated_in_filament(): void
    {
        $category = $this->createCategory('stati');
        $article = $this->createPage($category, 'article', '2026-06-01 10:00:00');
        $navigation = $article->blocks()
            ->where('type', BlockType::ARTICLE_NAVIGATION)
            ->firstOrFail();
        $content = Block::query()->create([
            'page_id' => $article->id,
            'type' => BlockType::POST_TEXT,
            'title' => 'Текст статьи',
        ]);

        $this->assertTrue(
            ReplicateBlockAction::make('replicate-navigation')
                ->record($navigation)
                ->isHidden()
        );
        $this->assertFalse(
            ReplicateBlockAction::make('replicate-content')
                ->record($content)
                ->isHidden()
        );
    }

    public function test_post_page_automatically_gets_one_navigation_block(): void
    {
        $category = $this->createCategory('stati');

        $article = $this->createPage($category, 'article', '2026-06-01 10:00:00');
        $this->createPage($category, 'blog', '2026-06-01 10:00:00', ['type' => PageType::Blog]);

        $this->assertSame(
            1,
            $article->blocks()
                ->where('type', BlockType::ARTICLE_NAVIGATION)
                ->count(),
        );
        $this->assertSame(
            1,
            Block::query()
                ->where('type', BlockType::ARTICLE_NAVIGATION)
                ->count(),
        );
    }

    public function test_navigation_is_placed_after_article_content_and_before_faq(): void
    {
        $category = $this->createCategory('stati');
        $article = $this->createPage($category, 'article', '2026-06-01 10:00:00');

        Block::query()->create([
            'page_id' => $article->id,
            'type' => BlockType::POST_TEXT,
            'title' => 'Первый текстовый блок',
        ]);
        Block::query()->create([
            'page_id' => $article->id,
            'type' => BlockType::EXPERT_OPINION,
            'title' => 'Мнение эксперта',
        ]);
        Block::query()->create([
            'page_id' => $article->id,
            'type' => BlockType::FAQ,
            'title' => 'Часто задаваемые вопросы',
        ]);
        Block::query()->create([
            'page_id' => $article->id,
            'type' => BlockType::CARDS_SLIDER,
            'title' => 'Читайте также',
        ]);

        $this->assertSame(
            [
                BlockType::POST_TEXT,
                BlockType::EXPERT_OPINION,
                BlockType::ARTICLE_NAVIGATION,
                BlockType::FAQ,
                BlockType::CARDS_SLIDER,
            ],
            $article->blocks()->pluck('type')->all(),
        );
    }

    public function test_navigation_without_faq_is_placed_after_last_article_content_block(): void
    {
        $category = $this->createCategory('stati');
        $article = $this->createPage($category, 'article', '2026-06-01 10:00:00');

        Block::query()->create([
            'page_id' => $article->id,
            'type' => BlockType::POST_TEXT,
            'title' => 'Первый текстовый блок',
        ]);
        Block::query()->create([
            'page_id' => $article->id,
            'type' => BlockType::CARDS_SLIDER,
            'title' => 'Читайте также',
        ]);

        $this->assertSame(
            [
                BlockType::POST_TEXT,
                BlockType::ARTICLE_NAVIGATION,
                BlockType::CARDS_SLIDER,
            ],
            $article->blocks()->pluck('type')->all(),
        );
    }

    public function test_navigation_is_removed_when_page_stops_being_a_post_and_restored_when_it_becomes_a_post_again(): void
    {
        $category = $this->createCategory('stati');
        $article = $this->createPage($category, 'article', '2026-06-01 10:00:00');

        $this->assertSame(1, $article->blocks()->where('type', BlockType::ARTICLE_NAVIGATION)->count());

        $article->update(['type' => PageType::Blog]);

        $this->assertSame(0, $article->blocks()->where('type', BlockType::ARTICLE_NAVIGATION)->count());

        $article->update(['type' => PageType::Posts]);

        $this->assertSame(1, $article->blocks()->where('type', BlockType::ARTICLE_NAVIGATION)->count());
    }

    public function test_positioning_does_not_update_blocks_when_order_is_already_correct(): void
    {
        $category = $this->createCategory('stati');
        $article = $this->createPage($category, 'article', '2026-06-01 10:00:00');

        Block::query()->create([
            'page_id' => $article->id,
            'type' => BlockType::POST_TEXT,
            'title' => 'Текст статьи',
        ]);

        $updates = [];
        DB::listen(function ($query) use (&$updates): void {
            if (str_starts_with(strtolower(ltrim($query->sql)), 'update "blocks"')) {
                $updates[] = $query->sql;
            }
        });

        app(ArticleNavigationBlockService::class)->positionExistingForPage($article);

        $this->assertSame([], $updates);
    }

    public function test_moving_a_block_repositions_navigation_on_both_pages(): void
    {
        $category = $this->createCategory('stati');
        $source = $this->createPage($category, 'source', '2026-06-02 10:00:00');
        $target = $this->createPage($category, 'target', '2026-06-01 10:00:00');

        $content = Block::query()->create([
            'page_id' => $source->id,
            'type' => BlockType::POST_TEXT,
            'title' => 'Переносимый текст',
        ]);

        $this->assertSame(
            2,
            $source->blocks()->where('type', BlockType::ARTICLE_NAVIGATION)->value('order_column'),
        );

        $content->update(['page_id' => $target->id]);

        $this->assertSame(
            1,
            $source->blocks()->where('type', BlockType::ARTICLE_NAVIGATION)->value('order_column'),
        );
        $this->assertSame(
            [BlockType::POST_TEXT, BlockType::ARTICLE_NAVIGATION],
            $target->blocks()->pluck('type')->all(),
        );
    }

    public function test_deferred_positioning_updates_block_order_only_once(): void
    {
        $category = $this->createCategory('stati');
        $article = $this->createPage($category, 'article', '2026-06-01 10:00:00');
        $service = app(ArticleNavigationBlockService::class);

        $updates = [];
        DB::listen(function ($query) use (&$updates): void {
            if (str_starts_with(strtolower(ltrim($query->sql)), 'update "blocks"')) {
                $updates[] = $query->sql;
            }
        });

        $service->deferPositioning($article, function () use ($article): void {
            foreach ([
                BlockType::POST_TEXT,
                BlockType::EXPERT_OPINION,
                BlockType::FAQ,
            ] as $index => $type) {
                Block::query()->create([
                    'page_id' => $article->id,
                    'type' => $type,
                    'title' => 'Блок ' . $index,
                ]);
            }
        });

        $this->assertCount(4, $updates);
        $this->assertSame(
            [
                BlockType::POST_TEXT,
                BlockType::EXPERT_OPINION,
                BlockType::ARTICLE_NAVIGATION,
                BlockType::FAQ,
            ],
            $article->blocks()->pluck('type')->all(),
        );
    }

    private function createCategory(string $handle): Category
    {
        return Category::query()->create([
            'title' => ucfirst($handle),
            'handle' => $handle,
        ]);
    }

    private function createPage(
        Category $category,
        string $handle,
        string $createdAt,
        array $overrides = [],
    ): Page {
        $page = Page::query()->create(array_merge([
            'title' => ucfirst($handle),
            'handle' => $handle,
            'type' => PageType::Posts,
            'active' => true,
            'category_id' => $category->id,
        ], $overrides));

        $page->timestamps = false;
        $page->created_at = $createdAt;
        $page->updated_at = $createdAt;
        $page->saveQuietly();
        $page->timestamps = true;

        return $page->fresh('category');
    }
}
