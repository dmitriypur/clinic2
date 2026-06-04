<?php

namespace Tests\Feature;

use App\Enums\PageType;
use App\Models\City;
use App\Models\Page;
use App\Services\CityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ArticleBookingConversionTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_booking_conversion_is_stored_for_active_article(): void
    {
        $city = $this->createCity();
        $page = $this->createArticle();

        $this->postJson('/api/article-booking-conversions', [
            'page_id' => $page->id,
            'city_id' => $city->id,
            'event_uuid' => (string) Str::uuid(),
            'page_url' => 'https://example.test/stati/test-article',
            'page_path' => '/stati/test-article',
            'entry_point' => 'booking_widget',
            'booking_mode' => 'doctor',
        ])
            ->assertCreated()
            ->assertJson([
                'count' => 1,
            ]);

        $this->assertDatabaseHas('article_booking_conversions', [
            'page_id' => $page->id,
            'city_id' => $city->id,
            'page_path' => '/stati/test-article',
            'entry_point' => 'booking_widget',
            'booking_mode' => 'doctor',
        ]);
    }

    public function test_duplicate_event_uuid_does_not_increment_counter(): void
    {
        $city = $this->createCity();
        $page = $this->createArticle();
        $eventUuid = (string) Str::uuid();

        $payload = [
            'page_id' => $page->id,
            'city_id' => $city->id,
            'event_uuid' => $eventUuid,
            'page_url' => 'https://example.test/stati/test-article',
            'page_path' => '/stati/test-article',
            'entry_point' => 'booking_widget',
            'booking_mode' => 'clinic',
        ];

        $this->postJson('/api/article-booking-conversions', $payload)
            ->assertCreated()
            ->assertJson(['count' => 1]);

        $this->postJson('/api/article-booking-conversions', $payload)
            ->assertOk()
            ->assertJson(['count' => 1]);

        $this->assertDatabaseCount('article_booking_conversions', 1);
    }

    public function test_non_article_page_is_not_counted(): void
    {
        $city = $this->createCity();
        $page = Page::query()->create([
            'title' => 'Default page',
            'handle' => 'default-page',
            'type' => PageType::Default,
            'active' => true,
        ]);

        $this->postJson('/api/article-booking-conversions', [
            'page_id' => $page->id,
            'city_id' => $city->id,
            'event_uuid' => (string) Str::uuid(),
            'page_url' => 'https://example.test/default-page',
            'page_path' => '/default-page',
            'entry_point' => 'booking_widget',
            'booking_mode' => 'date',
        ])->assertUnprocessable();

        $this->assertDatabaseCount('article_booking_conversions', 0);
    }

    public function test_helper_counts_conversions_for_current_city(): void
    {
        $currentCity = $this->createCity(['slug' => 'kirov', 'name' => 'Киров']);
        $otherCity = $this->createCity(['slug' => 'spb', 'name' => 'Санкт-Петербург']);
        $page = $this->createArticle();

        $page->articleBookingConversions()->createMany([
            [
                'city_id' => $currentCity->id,
                'event_uuid' => (string) Str::uuid(),
                'page_url' => 'https://example.test/stati/test-article',
                'page_path' => '/stati/test-article',
                'entry_point' => 'booking_widget',
                'booking_mode' => 'doctor',
            ],
            [
                'city_id' => $currentCity->id,
                'event_uuid' => (string) Str::uuid(),
                'page_url' => 'https://example.test/stati/test-article',
                'page_path' => '/stati/test-article',
                'entry_point' => 'booking_widget',
                'booking_mode' => 'clinic',
            ],
            [
                'city_id' => $otherCity->id,
                'event_uuid' => (string) Str::uuid(),
                'page_url' => 'https://example.test/spb/stati/test-article',
                'page_path' => '/spb/stati/test-article',
                'entry_point' => 'booking_widget',
                'booking_mode' => 'doctor',
            ],
        ]);

        app(CityService::class)->setCurrentCity($currentCity);

        $this->assertSame(2, article_booking_count($page));
        $this->assertSame(1, article_booking_count($page, $otherCity->id));
    }

    private function createArticle(array $overrides = []): Page
    {
        return Page::query()->create(array_merge([
            'title' => 'Test article',
            'handle' => 'test-article',
            'type' => PageType::Posts,
            'active' => true,
        ], $overrides));
    }

    private function createCity(array $overrides = []): City
    {
        return City::query()->create(array_merge([
            'name' => 'Москва',
            'slug' => 'moscow-' . Str::lower(Str::random(6)),
            'active' => true,
            'is_default' => false,
        ], $overrides));
    }
}
