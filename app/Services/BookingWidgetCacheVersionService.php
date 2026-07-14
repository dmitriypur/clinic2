<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class BookingWidgetCacheVersionService
{
    private const CACHE_KEY = 'booking-widget:local-data-version';

    public function current(): string
    {
        return (string) Cache::get(self::CACHE_KEY, '1');
    }

    public function bump(): string
    {
        $version = (string) Str::uuid();

        Cache::forever(self::CACHE_KEY, $version);

        return $version;
    }
}
