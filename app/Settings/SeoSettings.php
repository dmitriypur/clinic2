<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SeoSettings extends Settings
{
    public string $robots_txt;

    public array $scripts;
    
    public array $header_scripts;

    public bool $ignore_sitemap_last_mode;

    public string $logo_alt;

    public string $logo_title;

    public string $image_alt_template;

    public string $image_title_template;

    public static function group(): string
    {
        return 'seo';
    }

    public function logoAlt()
    {
        return $this->logo_alt === '' ? config('app.name') : $this->logo_alt;
    }

    public function logoTitle(): ?string
    {
        return $this->logo_title === '' ? null : $this->logo_title;
    }
}
