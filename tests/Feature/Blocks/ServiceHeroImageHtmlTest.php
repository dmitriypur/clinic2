<?php

namespace Tests\Feature\Blocks;

use DOMDocument;
use Tests\TestCase;

class ServiceHeroImageHtmlTest extends TestCase
{
    public function test_service_hero_templates_render_responsive_main_image_urls_without_vue(): void
    {
        foreach ([
            'components.banner.appointment',
            'components.banner.with-btn',
        ] as $view) {
            $html = view($view, ['block' => $this->serviceHeroBlock()])->render();

            $document = new DOMDocument();
            @$document->loadHTML($html);
            $picture = $document->getElementsByTagName('picture')->item(0);
            $sources = $picture?->getElementsByTagName('source');
            $image = $picture?->getElementsByTagName('img')->item(0);

            $this->assertSame('/media/service-mobile.jpg', $sources?->item(0)?->getAttribute('srcset'), "{$view} mobile image is available in initial HTML");
            $this->assertSame('/media/service-desktop.jpg', $sources?->item(1)?->getAttribute('srcset'), "{$view} desktop image is available in initial HTML");
            $this->assertSame('/media/service-desktop.jpg', $image?->getAttribute('src'), "{$view} fallback image is available in initial HTML");
            $this->assertFalse($sources?->item(0)?->hasAttribute(':srcset') ?? true, "{$view} mobile image does not wait for Vue");
            $this->assertFalse($sources?->item(1)?->hasAttribute(':srcset') ?? true, "{$view} desktop image does not wait for Vue");
            $this->assertFalse($image?->hasAttribute(':src') ?? true, "{$view} fallback image does not wait for Vue");
        }
    }

    private function serviceHeroBlock(): object
    {
        return new class {
            public array $payload = [
                'service_hero_title' => 'Проверка зрения',
                'service_hero_subtitle' => 'Безопасный тестовый баннер',
                'service_hero_text' => '<strong>Тестовый текст</strong>',
                'price' => '2500',
                'old_price' => '3000',
            ];

            public function getFirstMediaUrl(string $collection): string
            {
                return $collection === 'pic'
                    ? '/media/service-mobile.jpg'
                    : '/media/service-desktop.jpg';
            }
        };
    }
}
