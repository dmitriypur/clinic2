<?php

namespace Tests\Feature\Blocks;

use App\Blocks\BlockRegistry;
use App\Enums\BlockType;
use App\Models\Block;
use App\Services\ServiceHeroImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ServiceHeroBlockTest extends TestCase
{
    public function test_uploads_generate_webp_and_keep_originals(): void
    {
        Storage::fake('public');
        $images = app(ServiceHeroImageService::class);
        foreach (['jpg', 'png'] as $extension) {
            $path = $images->store(UploadedFile::fake()->image('hero.'.$extension, 80, 40));
            $webp = $images->webpPath($path);
            Storage::disk('public')->assertExists([$path, $webp]);
            $this->assertSame('image/webp', mime_content_type(Storage::disk('public')->path($webp)));
            $this->assertSame([80, 40], array_slice(getimagesize(Storage::disk('public')->path($webp)), 0, 2));
        }
    }

    public function test_generated_webp_is_preferred_and_original_is_available_as_fallback(): void
    {
        Storage::fake('public');
        $images = app(ServiceHeroImageService::class);
        $path = $images->store(UploadedFile::fake()->image('hero.jpg', 80, 40));
        $block = new Block(['type' => BlockType::SERVICE_HERO, 'title' => 'Услуга', 'payload' => ['image' => $path]]);
        $definition = app(BlockRegistry::class)->find(BlockType::SERVICE_HERO);
        $html = view($definition->view(), $definition->viewData($block))->render();
        $this->assertStringContainsString('type="image/webp" srcset="/storage/'.$images->webpPath($path).'"', $html);
        $this->assertStringContainsString('src="/storage/'.$path.'"', $html);
    }

    public function test_service_hero_renders_configured_content_and_booking_action(): void
    {
        $definition = app(BlockRegistry::class)->find(BlockType::SERVICE_HERO);
        $block = new Block(['type' => BlockType::SERVICE_HERO, 'title' => 'Проверка зрения', 'payload' => [
            'description' => 'Описание услуги', 'image' => 'service-hero/doctor.webp',
            'mobile_image' => 'service-hero/mobile.webp', 'button_text' => 'Записаться',
        ]]);
        $html = view($definition->view(), $definition->viewData($block))->render();
        $this->assertStringContainsString('Проверка зрения', $html);
        $this->assertStringContainsString('Описание услуги', $html);
        $this->assertStringContainsString('/storage/service-hero/doctor.webp', $html);
        $this->assertStringContainsString('/storage/service-hero/mobile.webp', $html);
        $this->assertStringContainsString("openBookingWidgetV3('otpravka-formy')", $html);
        $this->assertStringContainsString('Записаться', $html);
        $this->assertStringContainsString('object-contain object-bottom', $html);
        $this->assertStringNotContainsString('disclaimer-desktop.svg', $html);
    }

    public function test_mobile_image_falls_back_to_desktop_and_empty_description_is_hidden(): void
    {
        $definition = app(BlockRegistry::class)->find(BlockType::SERVICE_HERO);
        $block = new Block(['type' => BlockType::SERVICE_HERO, 'title' => 'Услуга', 'payload' => ['image' => 'service-hero/a.png']]);
        $data = $definition->viewData($block);
        $this->assertSame($data['desktopImage'], $data['mobileImage']);
        $this->assertSame('', $data['description']);
    }

    public function test_button_links_to_configured_url_instead_of_opening_booking(): void
    {
        $definition = app(BlockRegistry::class)->find(BlockType::SERVICE_HERO);
        foreach (['/services/exam', '#prices', 'https://example.org/page', 'tel:+78000000000'] as $url) {
            $block = new Block(['type' => BlockType::SERVICE_HERO, 'title' => 'Услуга', 'payload' => ['button_url' => $url]]);
            $html = view($definition->view(), $definition->viewData($block))->render();
            $this->assertStringContainsString('href="'.$url.'"', $html);
            $this->assertStringNotContainsString("openBookingWidgetV3('otpravka-formy')", $html);
        }
    }

    public function test_blank_or_unsafe_url_opens_booking(): void
    {
        $definition = app(BlockRegistry::class)->find(BlockType::SERVICE_HERO);
        foreach (['', '   ', 'javascript:alert(1)', '//example.org'] as $url) {
            $block = new Block(['type' => BlockType::SERVICE_HERO, 'title' => 'Услуга', 'payload' => ['button_url' => $url]]);
            $html = view($definition->view(), $definition->viewData($block))->render();
            $this->assertStringContainsString("openBookingWidgetV3('otpravka-formy')", $html);
            $this->assertStringNotContainsString('href=', $html);
        }
    }
}
