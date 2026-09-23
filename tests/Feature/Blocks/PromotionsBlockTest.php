<?php

namespace Tests\Feature\Blocks;

use App\Blocks\BlockRegistry;
use App\Enums\BlockType;
use App\Filament\Resources\PromotionResource;
use App\Models\Block;
use App\Models\City;
use App\Models\Promotion;
use App\Services\CityService;
use App\Services\PromotionBlockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

class PromotionsBlockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_registered_block_renders_real_lazy_image_urls_and_webp_sources(): void
    {
        $promotion = $this->promotion('Проверка зрения');
        $desktop = $this->media($promotion, 'default', 'desktop.jpg', 'image/jpeg', true);
        $mobile = $this->media($promotion, 'block_mobile', 'mobile.jpg', 'image/jpeg', true);

        $definition = app(BlockRegistry::class)->find(BlockType::PROMOTIONS);

        $this->assertNotNull($definition);

        $html = view($definition->view(), [
            'block' => $this->block(),
            ...$definition->viewData($this->block()),
        ])->render();

        $this->assertStringContainsString('src="'.$mobile->getUrl().'"', $html);
        $this->assertStringContainsString($desktop->getUrl('main'), $html);
        $this->assertStringContainsString($mobile->getUrl('main'), $html);
        $this->assertStringContainsString('loading="lazy"', $html);
        $this->assertStringContainsString('decoding="async"', $html);
        $this->assertStringNotContainsString('<image-lazy', $html);
        $this->assertStringNotContainsString(':src=', $html);
    }

    public function test_webp_original_is_used_instead_of_its_reencoded_conversion(): void
    {
        $promotion = $this->promotion('Линзы');
        $desktop = $this->media($promotion, 'default', 'desktop.webp', 'image/webp', true);
        $mobile = $this->media($promotion, 'block_mobile', 'mobile.webp', 'image/webp', true);
        $block = $this->block();
        $definition = app(BlockRegistry::class)->find(BlockType::PROMOTIONS);

        $html = view($definition->view(), [
            'block' => $block,
            ...$definition->viewData($block),
        ])->render();

        $this->assertStringContainsString($desktop->getUrl(), $html);
        $this->assertStringContainsString($mobile->getUrl(), $html);
        $this->assertStringNotContainsString($desktop->getUrl('main'), $html);
        $this->assertStringNotContainsString($mobile->getUrl('main'), $html);
    }

    public function test_responsive_webp_sources_do_not_embed_placeholder_data_in_html(): void
    {
        $promotion = $this->promotion('Детская оптика');
        $desktop = $this->media($promotion, 'default', 'desktop.jpg', 'image/jpeg', true);
        $desktop->responsive_images = [
            'main' => [
                'urls' => ['desktop___main_600_263.webp', 'desktop___main_1200_526.webp'],
                'base64svg' => 'data:image/svg+xml;base64,AAAA',
            ],
        ];
        $desktop->save();
        $block = $this->block();
        $definition = app(BlockRegistry::class)->find(BlockType::PROMOTIONS);

        $html = view($definition->view(), [
            'block' => $block,
            ...$definition->viewData($block),
        ])->render();

        $this->assertStringContainsString('600w', $html);
        $this->assertStringContainsString('1200w', $html);
        $this->assertStringNotContainsString('data:image/svg+xml;base64', $html);
    }

    public function test_missing_mobile_image_falls_back_to_desktop_image(): void
    {
        $promotion = $this->promotion('Оправы');
        $desktop = $this->media($promotion, 'default', 'desktop.jpg', 'image/jpeg');
        $block = $this->block();
        $definition = app(BlockRegistry::class)->find(BlockType::PROMOTIONS);

        $html = view($definition->view(), [
            'block' => $block,
            ...$definition->viewData($block),
        ])->render();

        $this->assertStringContainsString('src="'.$desktop->getUrl().'"', $html);
        $this->assertStringContainsString('Переход к акции', $html);
    }

    public function test_cached_selection_refreshes_media_metadata_after_conversion_finishes(): void
    {
        $promotion = $this->promotion('Очки');
        $media = $this->media($promotion, 'default', 'desktop.jpg', 'image/jpeg');
        $service = app(PromotionBlockService::class);

        $this->assertFalse($service->forCurrentCity()->first()->getFirstMedia()->hasGeneratedConversion('main'));

        $media->generated_conversions = ['main' => true];
        $media->save();

        $this->assertTrue($service->forCurrentCity()->first()->getFirstMedia()->hasGeneratedConversion('main'));
    }

    public function test_saving_promotion_invalidates_active_and_inactive_city_caches(): void
    {
        $active = $this->city('moskva', true);
        $inactive = $this->city('spb', false);
        $promotion = $this->promotion('Скидка');
        $service = app(PromotionBlockService::class);

        app(CityService::class)->setCurrentCity($active);
        $service->forCurrentCity();
        app(CityService::class)->setCurrentCity($inactive);
        $service->forCurrentCity();

        $this->assertTrue(Cache::has('active_promotions_moskva'));
        $this->assertTrue(Cache::has('active_promotions_spb'));

        $promotion->update(['title' => 'Новая скидка']);

        $this->assertFalse(Cache::has('active_promotions_moskva'));
        $this->assertFalse(Cache::has('active_promotions_spb'));
    }

    public function test_city_caches_are_isolated(): void
    {
        $moscow = $this->city('moskva', true);
        $spb = $this->city('spb', true);
        $this->promotion('Первая акция');
        $service = app(PromotionBlockService::class);

        app(CityService::class)->setCurrentCity($moscow);
        $this->assertSame(['Первая акция'], $service->forCurrentCity()->pluck('title')->all());

        Promotion::withoutEvents(fn () => $this->promotion('Вторая акция'));

        app(CityService::class)->setCurrentCity($spb);
        $this->assertCount(2, $service->forCurrentCity());

        app(CityService::class)->setCurrentCity($moscow);
        $this->assertSame(['Первая акция'], $service->forCurrentCity()->pluck('title')->all());
    }

    public function test_archived_promotions_are_hidden(): void
    {
        $this->promotion('Активная акция');
        $archived = $this->promotion('Архив');
        $archived->update(['archived' => true]);

        $this->assertSame(
            ['Активная акция'],
            app(PromotionBlockService::class)->forCurrentCity()->pluck('title')->all(),
        );
    }

    public function test_admin_cache_reset_clears_the_city_specific_entry(): void
    {
        Cache::put('active_promotions_moskva', 'stale', 3600);
        $this->city('moskva', true);

        PromotionResource::forgetPromotionsCache();

        $this->assertFalse(Cache::has('active_promotions_moskva'));
    }

    public function test_full_block_component_renders_through_the_registry(): void
    {
        $promotion = $this->promotion('Акция сентября');
        $this->media($promotion, 'default', 'desktop.jpg', 'image/jpeg');
        $block = $this->block();

        $html = Blade::render(
            '<x-block :block="$block" breadcrumbs-title="" page-title="" page-description="" />',
            ['block' => $block],
        );

        $this->assertStringContainsString('Акция сентября', $html);
        $this->assertStringContainsString('loading="lazy"', $html);
    }

    private function block(): Block
    {
        return new Block([
            'type' => BlockType::PROMOTIONS,
            'title' => 'Акции',
            'settings' => ['title_hidden' => false],
        ]);
    }

    private function promotion(string $title): Promotion
    {
        return Promotion::query()->create([
            'title' => $title,
            'description_html' => '/promotions',
            'archived' => false,
            'order_column' => 1,
        ]);
    }

    private function city(string $slug, bool $active): City
    {
        return City::query()->create([
            'name' => $slug,
            'slug' => $slug,
            'active' => $active,
            'is_default' => $slug === 'moskva',
        ]);
    }

    private function media(
        Promotion $promotion,
        string $collection,
        string $filename,
        string $mime,
        bool $converted = false,
    ): Media {
        return Media::query()->create([
            'model_type' => Promotion::class,
            'model_id' => $promotion->id,
            'collection_name' => $collection,
            'name' => $filename,
            'file_name' => $filename,
            'mime_type' => $mime,
            'disk' => 'public',
            'conversions_disk' => 'public',
            'size' => 100000,
            'manipulations' => [],
            'custom_properties' => [],
            'generated_conversions' => ['main' => $converted],
            'responsive_images' => [],
            'order_column' => 1,
        ]);
    }
}
