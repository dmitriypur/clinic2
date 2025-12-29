<?php

namespace App\Settings;

use Illuminate\Support\Str;
use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $site_name;

    public string $yandex_map_api_key;

    public ?string $favicon;

    public array $licenses;

    public ?string $promotion_company;
    public ?string $promotion_company_url;

    public static function group(): string
    {
        return 'general';
    }

    public function faviconMimeType(): string
    {
        return Str::after($this->favicon, '.') === 'svg' ? 'image/svg+xml' : 'image/png';
    }
}
