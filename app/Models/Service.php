<?php

namespace App\Models;

use App\Clinic;
use App\Jobs\RegenerateSitemap;
use App\Models\ServicePrice;
use App\Models\Traits\HasCityScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\HtmlableMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Service extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, HasCityScope;

    protected $fillable = [
        'title',
        'uuid',
        'parent_id',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $dispatchesEvents = [
        'created' => RegenerateSitemap::class,
        'updated' => RegenerateSitemap::class,
    ];

    protected static function booted()
    {
        static::saved(function () {
            self::clearServicesCache();
        });

        static::deleted(function () {
            self::clearServicesCache();
        });
    }

    public static function clearServicesCache(): void
    {
        $cityService = app(\App\Services\CityService::class);
        $slugs = $cityService->getActiveCities()->pluck('slug')->push('global');

        foreach ($slugs as $slug) {
            \Illuminate\Support\Facades\Cache::forget("services-with-prices-{$slug}");
        }
    }

    public function children(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function prices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ServicePrice::class);
    }

    public function cities(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(City::class);
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('main-png')
            ->width(400)
            ->height(400)
            ->format(Manipulations::FORMAT_PNG)
            ->withResponsiveImages();

        $this->addMediaConversion('main')
            ->width(400)
            ->height(400)
            ->format(Manipulations::FORMAT_WEBP)
            ->withResponsiveImages();
    }

    public function getResponsiveImageAttribute(): ?HtmlableMedia
    {
        if (!$this->hasMedia()) {
            return null;
        }

        return Clinic::responsiveImage($this->getFirstMedia(), $this->title);
    }
}
