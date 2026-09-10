<?php

namespace Tests\Feature\Blocks;

use App\Models\Block;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

class BannerGridImageDimensionsTest extends TestCase
{
    public function test_safe_media_dimensions_are_read_from_responsive_image_metadata(): void
    {
        $block = new Block();
        $block->exists = true;
        $block->setRelation('media', new EloquentCollection([
            new Media([
                'collection_name' => 'desktop-banner',
                'responsive_images' => [
                    'media_library_original' => [
                        'urls' => ['banner___media_library_original_1028_822.jpg'],
                    ],
                ],
            ]),
        ]));

        $this->assertSame(
            ['width' => 1028, 'height' => 822],
            $block->getSafeFirstMediaDimensions('desktop-banner'),
        );
        $this->assertNull($block->getSafeFirstMediaDimensions('missing-banner'));
    }

    public function test_banner_grid_templates_render_desktop_and_mobile_dimensions(): void
    {
        foreach ([
            'components.banner.banners-grid',
            'components.banner.banners-grid-k',
        ] as $view) {
            $html = view($view, ['block' => $this->bannerBlock()])->render();

            $document = new \DOMDocument();
            @$document->loadHTML($html);
            $pictures = $document->getElementsByTagName('picture');

            $this->assertCount(3, $pictures);

            foreach ($pictures as $index => $picture) {
                $source = $picture->getElementsByTagName('source')->item(0);
                $image = $picture->getElementsByTagName('img')->item(0);

                $this->assertSame('491', $source?->getAttribute('width'), "{$view} mobile banner {$index} width");
                $this->assertSame('491', $source?->getAttribute('height'), "{$view} mobile banner {$index} height");
                $this->assertSame($index === 0 ? '1028' : '890', $image?->getAttribute('width'), "{$view} desktop banner {$index} width");
                $this->assertSame($index === 0 ? '822' : '389', $image?->getAttribute('height'), "{$view} desktop banner {$index} height");
            }
        }
    }

    public function test_banner_grid_keeps_images_without_dimensions_when_metadata_is_missing(): void
    {
        $html = view('components.banner.banners-grid', [
            'block' => $this->bannerBlock(withDimensions: false),
        ])->render();

        $document = new \DOMDocument();
        @$document->loadHTML($html);
        $pictures = $document->getElementsByTagName('picture');

        $this->assertCount(3, $pictures);

        foreach ($pictures as $index => $picture) {
            $source = $picture->getElementsByTagName('source')->item(0);
            $image = $picture->getElementsByTagName('img')->item(0);

            $this->assertNotNull($source, "mobile banner {$index} remains rendered");
            $this->assertNotNull($image, "desktop banner {$index} remains rendered");
            $this->assertFalse($source->hasAttribute('width'));
            $this->assertFalse($source->hasAttribute('height'));
            $this->assertFalse($image->hasAttribute('width'));
            $this->assertFalse($image->hasAttribute('height'));
        }
    }

    private function bannerBlock(bool $withDimensions = true): object
    {
        return new class ($withDimensions) {
            public array $images = [
                ['uuid' => 'banner-1', 'title' => 'Первый баннер', 'url' => null],
                ['uuid' => 'banner-2', 'title' => 'Второй баннер', 'url' => null],
                ['uuid' => 'banner-3', 'title' => 'Третий баннер', 'url' => null],
            ];

            public function __construct(private readonly bool $withDimensions)
            {
            }

            public function hasMedia(string $collection): bool
            {
                return str_starts_with($collection, 'mobile_');
            }

            public function getImageUrl(string $collection): string
            {
                return "/media/{$collection}.jpg";
            }

            public function getImageAltText(string $title): string
            {
                return "{$title} фото";
            }

            public function getImageTitleText(string $title): string
            {
                return $title;
            }

            public function getSafeFirstMediaDimensions(string $collection): ?array
            {
                if (! $this->withDimensions) {
                    return null;
                }

                if (str_starts_with($collection, 'mobile_')) {
                    return ['width' => 491, 'height' => 491];
                }

                return $collection === 'banner-1'
                    ? ['width' => 1028, 'height' => 822]
                    : ['width' => 890, 'height' => 389];
            }
        };
    }
}
