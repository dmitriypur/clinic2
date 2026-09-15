<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicFilterValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider invalidPerpageProvider
     */
    public function test_public_filters_reject_invalid_perpage_values(string $url): void
    {
        $this->getJson($url)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('perpage');
    }

    public static function invalidPerpageProvider(): array
    {
        return [
            'reviews zero' => ['/api/review-filter?perpage=0'],
            'reviews fractional' => ['/api/review-filter?perpage=1.5'],
            'reviews excessive' => ['/api/review-filter?perpage=101'],
            'posts zero' => ['/stati?perpage=0'],
            'posts fractional' => ['/stati?perpage=1.5'],
            'posts excessive' => ['/stati?perpage=101'],
        ];
    }

    public function test_review_filter_keeps_accepting_integer_query_values_from_the_frontend(): void
    {
        $this->getJson('/api/review-filter?perpage=1&doctors[]=1&resources[]=2&services[]=3')
            ->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_post_filter_keeps_accepting_integer_query_values_from_the_frontend(): void
    {
        Category::query()->create([
            'title' => 'Статьи',
            'handle' => 'stati',
        ]);

        $this->getJson('/stati?perpage=1&tags[]=1')
            ->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    /**
     * @dataProvider invalidReviewFilterItemProvider
     */
    public function test_review_filter_rejects_non_integer_filter_items(string $field): void
    {
        $this->getJson("/api/review-filter?perpage=1&{$field}[0][nested]=1")
            ->assertUnprocessable()
            ->assertJsonValidationErrors("{$field}.0");
    }

    public static function invalidReviewFilterItemProvider(): array
    {
        return [
            'doctors' => ['doctors'],
            'resources' => ['resources'],
            'services' => ['services'],
        ];
    }

    public function test_post_filter_rejects_non_integer_filter_items(): void
    {
        $this->getJson('/stati?perpage=1&tags[0][nested]=1')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('tags.0');
    }
}
