<?php

namespace Tests\Unit;

use App\Models\Traits\HasSafeMediaConversions;
use PHPUnit\Framework\TestCase;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SafeMediaConversionsTest extends TestCase
{
    public function test_it_uses_requested_conversion_when_it_is_generated(): void
    {
        $helper = new class {
            use HasSafeMediaConversions;

            public function conversion(?Media $media, ?string $conversionName): string
            {
                return $this->safeConversionName($media, $conversionName);
            }
        };

        $media = new Media();
        $media->generated_conversions = ['main' => true];

        $this->assertSame('main', $helper->conversion($media, 'main'));
    }

    public function test_it_falls_back_to_original_when_conversion_is_not_generated(): void
    {
        $helper = new class {
            use HasSafeMediaConversions;

            public function conversion(?Media $media, ?string $conversionName): string
            {
                return $this->safeConversionName($media, $conversionName);
            }
        };

        $media = new Media();
        $media->generated_conversions = ['main' => false];

        $this->assertSame('', $helper->conversion($media, 'main'));
    }

    public function test_it_keeps_original_requests_unchanged(): void
    {
        $helper = new class {
            use HasSafeMediaConversions;

            public function conversion(?Media $media, ?string $conversionName): string
            {
                return $this->safeConversionName($media, $conversionName);
            }
        };

        $media = new Media();
        $media->generated_conversions = ['main' => true];

        $this->assertSame('', $helper->conversion($media, ''));
        $this->assertSame('', $helper->conversion($media, null));
    }
}
