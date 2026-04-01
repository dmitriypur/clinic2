<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'branches',
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
        'branches' => 'json',
        'utm_phones' => 'json',
        'show_special_schedule' => 'boolean',
        'header_scripts' => 'json',
        'body_scripts' => 'json',
    ];

    protected static function booted()
    {
        static::saving(function (City $city) {
            if ($city->is_default) {
                static::where('id', '!=', $city->id)->update(['is_default' => false]);
            }
        });

        static::saved(function (City $city) {
            \Illuminate\Support\Facades\Cache::forget('route_city_slugs');
            \Illuminate\Support\Facades\Cache::forget('default_city');
            \Illuminate\Support\Facades\Cache::forget('active_cities');

            // Сбрасываем кеш для конкретного города при изменении его slug
            \Illuminate\Support\Facades\Cache::forget("city_by_slug_{$city->slug}");

            // Если slug был изменен, сбрасываем кеш и для старого значения
            if ($city->isDirty('slug') && $city->getOriginal('slug')) {
                \Illuminate\Support\Facades\Cache::forget("city_by_slug_{$city->getOriginal('slug')}");
            }
        });

        static::deleted(function (City $city) {
            \Illuminate\Support\Facades\Cache::forget('route_city_slugs');
            \Illuminate\Support\Facades\Cache::forget('default_city');
            \Illuminate\Support\Facades\Cache::forget('active_cities');
            \Illuminate\Support\Facades\Cache::forget("city_by_slug_{$city->slug}");
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

    public function reviews(): BelongsToMany
    {
        return $this->belongsToMany(Review::class);
    }

    public function utmPhones(): HasMany
    {
        return $this->hasMany(CityUtmPhone::class);
    }

    public function utmSources(): HasMany
    {
        return $this->hasMany(CityUtmSource::class);
    }

    public function utmMediums(): HasMany
    {
        return $this->hasMany(CityUtmMedium::class);
    }
}
