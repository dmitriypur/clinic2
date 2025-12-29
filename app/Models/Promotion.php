<?php

namespace App\Models;

use App\Settings\SeoSettings;
use App\Models\Traits\HasCityScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\HtmlableMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property string $title
 * @property string $description_html
 * @property Carbon $published_at
 * @property bool $archived
 * @property numeric $order_column
 */
class Promotion extends Model implements HasMedia, Sortable
{
    use HasFactory, InteractsWithMedia, SortableTrait, HasCityScope;

    protected $fillable = [
        'title',
        'description_html',
        'published_at',
        'archived',
        'order_column',
    ];

    protected $casts = [
        'archived' => 'bool',
        'published_at' => 'timestamp',
    ];

    protected static function booted()
    {
        static::saved(function () {
            self::clearPromotionsCache();
        });

        static::deleted(function () {
            self::clearPromotionsCache();
        });
    }

    private static function clearPromotionsCache(): void
    {
        \Illuminate\Support\Facades\Cache::forget('active_promotions');
        
        $cityService = app(\App\Services\CityService::class);
        $slugs = $cityService->getActiveCities()->pluck('slug')->push('global');
        
        foreach ($slugs as $slug) {
            \Illuminate\Support\Facades\Cache::forget("active_promotions_{$slug}");
        }
    }

    public function cities(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(City::class);
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('main')
            ->withResponsiveImages()
            ->format('webp')
            ->width(1600)
            ->height(960);

        $this->addMediaConversion('main-png')
            ->withResponsiveImages()
            ->format('png')
            ->width(1600)
            ->height(960);
    }

    public function getResponsiveImageAttribute(): ?HtmlableMedia
    {
        if (!$this->hasMedia()) {
            return null;
        }

        $settings = app(SeoSettings::class);

        return $this->getFirstMedia()->img('main')->attributes([
            'alt' => Str::of($settings->image_alt_template)->replace('{h1}', $this->title)->trim()->value(),
            'title' => Str::of($settings->image_title_template)->replace('{h1}', $this->title)->trim()->value(),
            'itemprop' => 'contentUrl'
        ]);
    }
}
