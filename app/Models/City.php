<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $name
 * @property string $slug
 * @property bool $is_default
 * @property bool $active
 * @property array $contacts
 * @property array $seo_cases
 */
class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'is_default',
        'active',
        'seo_cases',
        'phone',
        'email',
        'address',
        'postal_code',
        'coordinates',
        'schedule',
        'metro',
        'social_links',
        'utm_phones',
        'special_schedule',
        'show_special_schedule',
        'special_schedule_title',
        'header_scripts',
        'body_scripts',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'active' => 'boolean',
        'seo_cases' => 'json',
        'social_links' => 'json',
        'utm_phones' => 'json',
        'show_special_schedule' => 'boolean',
        'header_scripts' => 'json',
        'body_scripts' => 'json',
    ];

    protected static function booted()
    {
        static::saved(function () {
            \Illuminate\Support\Facades\Cache::forget('route_city_slugs');
            \Illuminate\Support\Facades\Cache::forget('default_city');
            \Illuminate\Support\Facades\Cache::forget('active_cities');
        });

        static::deleted(function () {
            \Illuminate\Support\Facades\Cache::forget('route_city_slugs');
            \Illuminate\Support\Facades\Cache::forget('default_city');
            \Illuminate\Support\Facades\Cache::forget('active_cities');
        });
    }

    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(Page::class);
    }

    public function doctors(): BelongsToMany
    {
        return $this->belongsToMany(Doctor::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class);
    }

    public function promotions(): BelongsToMany
    {
        return $this->belongsToMany(Promotion::class);
    }

    public function blocks(): BelongsToMany
    {
        return $this->belongsToMany(Block::class, 'city_block');
    }
}
