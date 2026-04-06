<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PageType;
use App\Models\City;
use App\Models\Doctor;
use App\Models\Page;
use App\Models\Review;
use App\Services\CityService;
use App\Services\YmlFeedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class YmlFeedTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        $this->deleteGeneratedFeedFiles();

        parent::tearDown();
    }

    public function test_default_city_feed_contains_only_default_and_global_entities(): void
    {
        [$moscow, $kirov] = $this->createCities();

        $globalDoctor = $this->createDoctor('Глобалов', 'Григорий Петрович');
        $moscowDoctor = $this->createDoctor('Москов', 'Михаил Сергеевич', $moscow);
        $kirovDoctor = $this->createDoctor('Киров', 'Кирилл Андреевич', $kirov);

        $globalService = $this->createService('Глобальная услуга', 'global-service');
        $moscowService = $this->createService('Московская услуга', 'moscow-service', $moscow);
        $this->createService('Кировская услуга', 'kirov-service', $kirov);

        $review = new Review([
            'name' => 'Тестовый пациент',
            'body_html' => 'Отличный врач!',
            'rating' => 5,
        ]);
        $review->service_uuid = (string) fake()->uuid();
        $review->doctor_id = $moscowDoctor->id;
        $review->save();

        /** @var YmlFeedService $service */
        $service = app(YmlFeedService::class);
        $feedContent = $service->generateDoctorsFeed($moscow);

        $this->assertStringContainsString('Глобалов Григорий Петрович', $feedContent);
        $this->assertStringContainsString('Москов Михаил Сергеевич', $feedContent);
        $this->assertStringNotContainsString('Киров Кирилл Андреевич', $feedContent);

        $this->assertStringContainsString('Глобальная услуга', $feedContent);
        $this->assertStringContainsString('Московская услуга', $feedContent);
        $this->assertStringNotContainsString('Кировская услуга', $feedContent);

        $baseUrl = rtrim((string) config('app.url'), '/');

        $this->assertStringContainsString($baseUrl . '/doctors/' . $moscowDoctor->handle, $feedContent);
        $this->assertStringContainsString($baseUrl . '/moscow-service', $feedContent);
        $this->assertStringContainsString('Тестовый пациент', $feedContent);
        $this->assertStringContainsString('Отличный врач!', $feedContent);
        $this->assertStringContainsString('<city>Москва</city>', $feedContent);
        $this->assertStringNotContainsString('/kirov/doctors/' . $moscowDoctor->handle, $feedContent);
        $this->assertStringNotContainsString('/kirov-service', $feedContent);
    }

    public function test_non_default_city_feed_contains_city_prefix_and_excludes_other_city_entities(): void
    {
        [$moscow, $kirov] = $this->createCities();

        $globalDoctor = $this->createDoctor('Глобалов', 'Григорий Петрович');
        $kirovDoctor = $this->createDoctor('Киров', 'Кирилл Андреевич', $kirov);
        $this->createDoctor('Москов', 'Михаил Сергеевич', $moscow);

        $globalService = $this->createService('Глобальная услуга', 'global-service');
        $kirovService = $this->createService('Кировская услуга', 'kirov-service', $kirov);
        $this->createService('Московская услуга', 'moscow-service', $moscow);

        /** @var YmlFeedService $service */
        $service = app(YmlFeedService::class);
        $feedContent = $service->generateDoctorsFeed($kirov);

        $this->assertStringContainsString('Глобалов Григорий Петрович', $feedContent);
        $this->assertStringContainsString('Киров Кирилл Андреевич', $feedContent);
        $this->assertStringNotContainsString('Москов Михаил Сергеевич', $feedContent);

        $this->assertStringContainsString('Глобальная услуга', $feedContent);
        $this->assertStringContainsString('Кировская услуга', $feedContent);
        $this->assertStringNotContainsString('Московская услуга', $feedContent);

        $baseUrl = rtrim((string) config('app.url'), '/');

        $this->assertStringContainsString($baseUrl . '/kirov/doctors/' . $kirovDoctor->handle, $feedContent);
        $this->assertStringContainsString($baseUrl . '/kirov/global-service', $feedContent);
        $this->assertStringContainsString($baseUrl . '/kirov/kirov-service', $feedContent);
        $this->assertStringContainsString('<city>Киров</city>', $feedContent);
        $this->assertStringNotContainsString($baseUrl . '/doctors/', $feedContent);
    }

    public function test_feed_files_are_saved_per_city_with_legacy_default_alias(): void
    {
        $this->deleteGeneratedFeedFiles();

        [$moscow, $kirov] = $this->createCities();
        $this->createDoctor('Москов', 'Михаил Сергеевич', $moscow);
        $this->createDoctor('Киров', 'Кирилл Андреевич', $kirov);
        $this->createService('Глобальная услуга', 'global-service');

        /** @var YmlFeedService $service */
        $service = app(YmlFeedService::class);
        $feeds = $service->generateDoctorsFeedsForActiveCities();
        $savedFeeds = $service->saveFeedsToFiles($feeds);

        $this->assertCount(2, $savedFeeds);
        $this->assertTrue(Storage::disk('public')->exists('doctors_feed.xml'));
        $this->assertTrue(Storage::disk('public')->exists('doctors_feed_moscow.xml'));
        $this->assertTrue(Storage::disk('public')->exists('doctors_feed_kirov.xml'));

        $this->assertSame(
            ['doctors_feed_moscow.xml', 'doctors_feed_kirov.xml'],
            collect($savedFeeds)->pluck('filename')->all()
        );

        $legacyContent = Storage::disk('public')->get('doctors_feed.xml');
        $defaultContent = Storage::disk('public')->get('doctors_feed_moscow.xml');

        $this->assertSame($defaultContent, $legacyContent);
    }

    public function test_single_city_file_save_does_not_write_legacy_alias_for_non_default_city(): void
    {
        $this->deleteGeneratedFeedFiles();

        [$moscow, $kirov] = $this->createCities();
        $this->createDoctor('Киров', 'Кирилл Андреевич', $kirov);
        $this->createService('Кировская услуга', 'kirov-service', $kirov);

        /** @var YmlFeedService $service */
        $service = app(YmlFeedService::class);
        $feedContent = $service->generateDoctorsFeed($kirov);
        $filename = $service->saveFeedToFile($feedContent, $kirov);

        $this->assertSame('doctors_feed_kirov.xml', $filename);
        $this->assertTrue(Storage::disk('public')->exists('doctors_feed_kirov.xml'));
        $this->assertFalse(Storage::disk('public')->exists('doctors_feed.xml'));
        $this->assertFalse(Storage::disk('public')->exists('doctors_feed_moscow.xml'));
    }

    private function createCities(): array
    {
        $moscow = City::query()->create([
            'name' => 'Москва',
            'slug' => 'moscow',
            'is_default' => true,
            'active' => true,
        ]);

        $kirov = City::query()->create([
            'name' => 'Киров',
            'slug' => 'kirov',
            'is_default' => false,
            'active' => true,
        ]);

        app(CityService::class)->setCurrentCity($moscow);

        return [$moscow, $kirov];
    }

    private function createDoctor(string $surname, string $name, ?City $city = null): Doctor
    {
        $doctor = Doctor::query()->create([
            'uuid' => (string) fake()->uuid(),
            'name' => $name,
            'surname' => $surname,
            'speciality' => 'Офтальмолог',
            'job_title' => 'Врач-офтальмолог',
            'bio' => 'Биография врача',
            'excerpt' => 'Тестовое описание врача',
            'extra' => [
                'seniority' => '10 лет',
                'category' => 'Первая',
            ],
            'handle' => str($surname . '-' . $name)->slug()->value(),
        ]);

        if ($city) {
            $doctor->cities()->attach($city);
        }

        return $doctor;
    }

    private function createService(string $title, string $handle, ?City $city = null): Page
    {
        $page = Page::query()->create([
            'title' => $title,
            'handle' => $handle,
            'body_html' => "<p>{$title}</p>",
            'active' => true,
            'type' => PageType::Services,
            'sorting' => 1,
            'seo' => [
                'price' => '1000',
            ],
        ]);

        if ($city) {
            $page->cities()->attach($city);
        }

        return $page;
    }

    private function deleteGeneratedFeedFiles(): void
    {
        $feedFiles = collect(Storage::disk('public')->files())
            ->filter(fn (string $file): bool => str_starts_with($file, 'doctors_feed') && str_ends_with($file, '.xml'))
            ->all();

        if ($feedFiles !== []) {
            Storage::disk('public')->delete($feedFiles);
        }
    }
}
