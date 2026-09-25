<?php

namespace Tests\Feature;

use App\Enums\PageType;
use App\Models\Category;
use App\Models\City;
use App\Models\Page;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTagFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_filter_includes_tags_from_articles_beyond_first_page_only_in_current_category(): void
    {
        City::query()->create([
            'name' => 'Киров',
            'slug' => 'kirov',
            'is_default' => true,
            'active' => true,
            'details' => [['name' => 'Тестовая клиника']],
        ]);

        $category = Category::query()->create(['title' => 'Статьи', 'handle' => 'stati']);
        $otherCategory = Category::query()->create(['title' => 'Справочник', 'handle' => 'directory']);

        Page::query()->create([
            'title' => 'Блог',
            'handle' => 'blog',
            'type' => PageType::Blog,
            'active' => true,
            'category_id' => $category->id,
        ]);

        $laterTag = Tag::query()->create(['title' => 'Поздний тег', 'handle' => 'later-tag']);
        $visibleTag = Tag::query()->create(['title' => 'Видимый тег', 'handle' => 'visible-tag']);
        $inactiveTag = Tag::query()->create(['title' => 'Неактивный тег', 'handle' => 'inactive-tag']);
        $otherCategoryTag = Tag::query()->create(['title' => 'Чужой тег', 'handle' => 'other-tag']);
        Tag::query()->create(['title' => 'Неиспользуемый тег', 'handle' => 'unused-tag']);

        $olderArticle = $this->createArticle($category, 'older-article');
        $olderArticle->tags()->attach($laterTag);

        for ($index = 1; $index <= 12; $index++) {
            $article = $this->createArticle($category, "recent-article-{$index}");
            if ($index <= 2) {
                $article->tags()->attach($visibleTag);
            }
        }

        $inactiveArticle = $this->createArticle($category, 'inactive-article', false);
        $inactiveArticle->tags()->attach($inactiveTag);

        $otherArticle = $this->createArticle($otherCategory, 'other-article');
        $otherArticle->tags()->attach($otherCategoryTag);

        $response = $this->get('/stati')->assertOk();
        $posts = $response->viewData('posts');
        $tags = $response->viewData('filter')['tags'];

        $this->assertSame(13, $posts->total());
        $this->assertFalse($posts->getCollection()->contains('id', $olderArticle->id));
        $this->assertSame([
            $visibleTag->id => $visibleTag->title,
            $laterTag->id => $laterTag->title,
        ], $tags->all());

        $this->getJson('/stati?perpage=1&tags[]=' . $laterTag->id)
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.title', 'older-article');

        $directoryTags = $this->get('/directory')->assertOk()->viewData('filter')['tags'];
        $this->assertSame([$otherCategoryTag->id => $otherCategoryTag->title], $directoryTags->all());
    }

    private function createArticle(Category $category, string $handle, bool $active = true): Page
    {
        return Page::query()->create([
            'title' => $handle,
            'handle' => $handle,
            'type' => PageType::Posts,
            'active' => $active,
            'category_id' => $category->id,
        ]);
    }
}
