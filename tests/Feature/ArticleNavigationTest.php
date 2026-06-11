<?php

namespace Tests\Feature;

use App\Enums\BlockType;
use App\Enums\PageType;
use App\Models\Block;
use App\Models\Category;
use App\Models\Page;
use App\Services\ArticleOrderingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ArticleNavigationTest extends TestCase
{
    use RefreshDatabase;

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

        Block::query()->create([
            'page_id' => $article->id,
            'type' => BlockType::ARTICLE_NAVIGATION,
            'title' => 'Навигация по статьям',
        ]);

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
