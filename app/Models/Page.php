<?php

namespace App\Models;

use App\Enums\BlockType;
use App\Enums\PageType;
use App\Jobs\RegenerateSitemap;
use App\Models\Traits\Filterable;
use App\Models\Traits\HasCityScope;
use App\Models\Traits\HasSafeMediaConversions;
use App\Support\CitySeoVariables;
use App\Settings\SeoSettings;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\HtmlableMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * @property string $id
 * @property string $title
 * @property ?string $breadcrumbs_title
 * @property bool $is_price_page
 * @property string $handle
 * @property string $seo
 */
class Page extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, HasSlug, Filterable, HasCityScope, HasSafeMediaConversions;
    protected $dispatchesEvents = [
        'created' => RegenerateSitemap::class,
        'updated' => RegenerateSitemap::class,
    ];

    protected static function booted()
    {
        static::saved(function (Page $page) {
            $page->clearCache();
        });

        static::deleting(function (Page $page) {
            $page->blocks()->with('media')->get()->each(function (Block $block) use ($page) {
                $block->setRelation('page', $page);
                $block->delete();
            });

            $page->tags()->detach();
            $page->cities()->detach();
            $page->reviews()->detach();
        });

        static::deleted(function (Page $page) {
            $page->clearCache();
        });
    }

    public function clearCache(): void
    {
        $cityService = app(\App\Services\CityService::class);
        $slugs = $cityService->getActiveCities()->pluck('slug')->push('global');
        $categoryHandle = $this->relationLoaded('category')
            ? $this->category?->handle
            : $this->category()->value('handle');

        foreach ($slugs as $slug) {
            \Illuminate\Support\Facades\Cache::forget("page-{$slug}-{$this->handle}");
            if ($categoryHandle) {
                \Illuminate\Support\Facades\Cache::forget("page-{$slug}-{$categoryHandle}/{$this->handle}");
            }
        }
    }

    protected $fillable = [
        'title',
        'subtitle',
        'breadcrumbs_title',
        'services_tags',
        'handle',
        'body_html',
        'seo',
        'is_price_page',
        'active',
        'type',
        'header_scripts',
        'sorting',
        'category_id'
    ];

    protected $casts = [
        'seo' => 'json',
        'is_price_page' => 'bool',
        'active' => 'bool',
        'type' => PageType::class,
    ];

    public function category(): BelongsTo
    {
        return $this->BelongsTo(Category::class);
    }

    public function cities(): BelongsToMany
    {
        return $this->belongsToMany(City::class);
    }

    public function reviews(): BelongsToMany
    {
        return $this->belongsToMany(Review::class);
    }

    public function articleBookingConversions(): HasMany
    {
        return $this->hasMany(ArticleBookingConversion::class);
    }

    public function articleViewCounter(): HasOne
    {
        return $this->hasOne(ArticleViewCounter::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('main')
            ->withResponsiveImages()
            ->format(Manipulations::FORMAT_WEBP)
            ->fit(Manipulations::FIT_CROP, 356, 220)
            ->performOnCollections('default');

        $this->addMediaConversion('thumb')
            ->withResponsiveImages()
            ->format(Manipulations::FORMAT_WEBP)
            ->fit(Manipulations::FIT_CROP, 356, 220)
            ->performOnCollections('documents');
    }
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('handle')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(Block::class)
            ->orderBy('order_column');
    }

    public function filteredBlocks(): HasMany
    {
        return $this->hasMany(Block::class)
            ->where('type', '!=', BlockType::TAGS)
            ->orderBy('order_column');
    }


    public function tagsBlock(): HasOne
    {
        return $this->hasOne(Block::class)->where('type', BlockType::TAGS);
    }

    public function pictureBlock(): HasOne
    {
        return $this->hasOne(Block::class)->where('type', BlockType::PICTURE);
    }

    public function authorBlock(): HasOne
    {
        return $this->hasOne(Block::class)->where('type', BlockType::AUTHOR);
    }

    public function readingTimeMinutes(): Attribute
    {
        return Attribute::make(get: function (): int {
            $blocks = $this->relationLoaded('blocks')
                ? $this->blocks->where('type', BlockType::POST_TEXT)
                : $this->blocks()
                    ->where('type', BlockType::POST_TEXT)
                    ->get(['body_html']);

            $text = $blocks
                ->pluck('body_html')
                ->filter()
                ->map(fn(string $html): string => html_entity_decode(
                    strip_tags($html),
                    ENT_QUOTES | ENT_HTML5,
                    'UTF-8'
                ))
                ->implode(' ');

            preg_match_all('/[\p{L}\p{N}]+(?:[\'’-][\p{L}\p{N}]+)*/u', $text, $matches);

            return max(1, (int) ceil(count($matches[0]) / 200));
        })->shouldCache();
    }

    public function getBreadcrumbsTitleAttribute(?string $value): string
    {
        return $value ?? $this->title;
    }

    public function paragraphCount(): Attribute
    {
        return Attribute::make(get: function () {
            preg_match('/<p>(.*?)<\/p>/s', $this->body_html, $matches);

            return count($matches);
        })->shouldCache();
    }

    public function firstParagraph(): Attribute
    {
        return Attribute::make(get: fn() => Str::before($this->body_html, '</p>') . '</p>')->shouldCache();
    }

    public function afterFirstParagraph(): Attribute
    {
        return Attribute::make(get: fn() => Str::after($this->body_html, '</p>'))->shouldCache();
    }


    public function getImageUrl(string $collection): string
    {
        return $this->getFirstMediaUrl($collection);
    }

    public function getResponsiveImage(string $collection, string $title, ?string $conversion = '')
    {
        $settings = app(SeoSettings::class);

        return $this->safeMediaImage($this->getFirstMedia($collection), $conversion)?->attributes([
            'alt' => Str::of($settings->image_alt_template)->replace('{h1}', $title)->value(),
            'title' => Str::of($settings->image_title_template)->replace('{h1}', $title)->value(),
        ]);
    }

    public function getUrl()
    {
        $cityService = app(\App\Services\CityService::class);
        $path = '/' . ltrim($this->handle, '/');

        if ($this->relationLoaded('category') && $this->category) {
            $path = "/{$this->category->handle}/{$this->handle}";
            return url($cityService->addCityPrefix($path));
        }

        if (!$this->relationLoaded('category') && $this->category_id) {
            $this->load('category');
            $path = "/{$this->category->handle}/{$this->handle}";
            return url($cityService->addCityPrefix($path));
        }

        return url($cityService->addCityPrefix($path));
    }

    // Добавить в модель Page

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeWithFullRelations($query)
    {
        return $query->with(['blocks.media', 'category', 'tags']);
    }

    public function scopeByHandle($query, string $handle)
    {
        return $query->where('handle', $handle);
    }

    // Оптимизированный метод для получения SEO данных
    public function getSeoTitle(): string
    {
        return $this->seo['title'] ?? $this->title;
    }

    public function getSeoDescription(): string
    {
        return $this->seo['description'] ?? '';
    }

    public function withResolvedCitySeoVariables(): self
    {
        $page = clone $this;
        $replacer = app(CitySeoVariables::class);

        $page->title = $replacer->replace((string) $page->title) ?? '';
        $page->body_html = $replacer->replace($page->body_html);

        if ($page->getRawOriginal('breadcrumbs_title') !== null) {
            $page->breadcrumbs_title = $replacer->replace((string) $page->getRawOriginal('breadcrumbs_title')) ?? '';
        }

        $seo = (array) ($page->seo ?? []);
        $seo['title'] = $replacer->replace($seo['title'] ?? null);
        $seo['description'] = $replacer->replace($seo['description'] ?? null);
        $page->seo = $seo;

        if ($page->relationLoaded('blocks')) {
            $blocks = $page->blocks->map(function (Block $block) use ($page): Block {
                $resolvedBlock = $block->withResolvedCitySeoVariables();
                $resolvedBlock->setRelation('page', $page);

                return $resolvedBlock;
            });

            $page->setRelation(
                'blocks',
                new Collection($blocks->all())
            );
        }

        return $page;
    }
}
