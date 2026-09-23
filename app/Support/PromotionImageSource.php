<?php

namespace App\Support;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\ResponsiveImages\ResponsiveImage;

final class PromotionImageSource
{
    public static function webpSrcset(Media $media): ?string
    {
        if ($media->mime_type === 'image/webp') {
            return $media->getUrl();
        }

        if (! $media->hasGeneratedConversion('main')) {
            return null;
        }

        $srcset = $media->responsiveImages('main')->files
            ->map(fn (ResponsiveImage $image): string => $image->url().' '.$image->width().'w')
            ->implode(', ');

        return $srcset ?: $media->getUrl('main');
    }
}
