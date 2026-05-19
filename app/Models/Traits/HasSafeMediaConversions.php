<?php

namespace App\Models\Traits;

use Spatie\MediaLibrary\MediaCollections\HtmlableMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait HasSafeMediaConversions
{
    protected function safeConversionName(?Media $media, ?string $conversionName): string
    {
        return self::resolveSafeConversionName($media, $conversionName);
    }

    protected static function resolveSafeConversionName(?Media $media, ?string $conversionName): string
    {
        if (blank($conversionName)) {
            return '';
        }

        return $media?->hasGeneratedConversion($conversionName) ? $conversionName : '';
    }

    public function getSafeFirstMediaUrl(string $collectionName = 'default', string $conversionName = ''): string
    {
        $media = $this->getFirstMedia($collectionName);

        return $media?->getUrl($this->safeConversionName($media, $conversionName)) ?? '';
    }

    protected function safeMediaImage(?Media $media, ?string $conversionName = ''): ?HtmlableMedia
    {
        return $media?->img($this->safeConversionName($media, $conversionName));
    }

    public function getSafeMediaImage(?Media $media, ?string $conversionName = ''): ?HtmlableMedia
    {
        return $this->safeMediaImage($media, $conversionName);
    }

    public static function safeMediaImageFor(?Media $media, ?string $conversionName = ''): ?HtmlableMedia
    {
        return $media?->img(self::resolveSafeConversionName($media, $conversionName));
    }
}
