<?php

namespace App\Models;

use App\Clinic;
use App\Enums\BlockType;
use App\Enums\PageType;
use App\Helpers\Doctors;
use App\Models\Traits\HasCityScope;
use App\Settings\GeneralSettings;
use App\Settings\SeoSettings;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property string $id
 * @property BlockType $type
 * @property string $title
 * @property string $anchor
 * @property string $body_html
 * @property integer $order_column
 * @property array $images
 * @property array $settings
 * @property array $payload
 * @property bool $title_hidden
 */
class Block extends Model implements HasMedia, Sortable
{
    use HasFactory, InteractsWithMedia, SortableTrait, HasCityScope;

    protected $fillable = [
        'page_id',
        'title',
        'anchor',
        'body_html',
        'type',
        'images',
        'settings',
        'payload',
    ];

    protected $casts = [
        'type' => BlockType::class,
        'images' => 'json',
        'settings' => 'json',
        'payload' => 'json',
    ];

    protected static function boot()
    {
        parent::boot();

        static::updated(function ($block) {
            Cache::forget('services_with_media_and_prices');
            $block->page?->clearCache();
        });

        static::deleted(function ($block) {
            Cache::forget('services_with_media_and_prices');
            $block->page?->clearCache();
        });
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('main')
            ->width(400)
            ->height(400)
            ->format(Manipulations::FORMAT_WEBP)
            ->withResponsiveImages();

        $this->addMediaConversion('main-post')
            ->width(500)
            ->height(500)
            ->format(Manipulations::FORMAT_WEBP)
            ->withResponsiveImages()
            ->performOnCollections('default');

        $this->addMediaConversion('main-post-750')
            ->width(750)
            ->height(750)
            ->format(Manipulations::FORMAT_WEBP)
            ->withResponsiveImages()
            ->performOnCollections('default');

        $this->addMediaConversion('main-png')
            ->width(400)
            ->height(400)
            ->format(Manipulations::FORMAT_PNG)
            ->withResponsiveImages();
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(Page::class);
    }

    public function cities(): BelongsToMany
    {
        return $this->belongsToMany(City::class, 'city_block');
    }

//    public function elements(): HasMany
//    {
//        return $this->hasMany(Element::class)->orderBy('order_column');
//    }

    public function getResponsiveImage(string $collection, string $title, ?string $conversion = '')
    {
        $settings = app(SeoSettings::class);

        return $this->getFirstMedia($collection)?->img($conversion)->attributes([
            'alt' => Str::of($settings->image_alt_template)->replace('{h1}', $title)->value(),
            'title' => Str::of($settings->image_title_template)->replace('{h1}', $title)->value(),
        ]);
    }

    public function getImageUrl(string $collection): string
    {
        return $this->getFirstMediaUrl($collection);
    }


    public function getTitleHiddenAttribute()
    {
        return data_get($this->settings, 'title_hidden', false);
    }

    public function getShowBreadcrumbsAttribute()
    {
        return data_get($this->settings, 'breadcrumbs', false);
    }

    public function getShowOnMobileAttribute()
    {
        return data_get($this->settings, 'show_on_mobile', true);
    }

    public function getHideOnDesctopAttribute()
    {
        return data_get($this->settings, 'hide_on_desctop', false);
    }

    public function getShowPageTitleAttribute()
    {
        return data_get($this->settings, 'show_page_title', false);
    }

    public function getPricesAttribute()
    {
        if ($this->type !== BlockType::PRICE_LIST) {
            return null;
        }

        if (!$this->payload['service']) {
            return null;
        }

        // Получаем сервис по UUID
        $serviceUuid = $this->payload['service'];
        
        $servicePriceService = app(\App\Services\ServicePriceService::class);
        $service = $servicePriceService->getServiceByUuid($serviceUuid);

        if (!$service) {
            return [];
        }

        // Формируем список цен для дочерних услуг
        return $service->children->map(function ($child) {
            $priceModel = $child->current_price;

            if (!$priceModel) {
                return null;
            }

            return [
                'item' => $child->title,
                'price1' => number_format($priceModel->price, 0, '.', ' '),
                'price2' => $priceModel->old_price ? number_format($priceModel->old_price, 0, '.', ' ') : 0,
                'price_from' => $priceModel->price_from,
            ];
        })->filter()->values()->toArray();
    }

    public function getFullPriceListAttribute(): Collection|null
    {
        if ($this->type !== BlockType::FULL_PRICE_LIST) {
            return null;
        }

        return Cache::remember('services_with_media_and_prices', 2592000, function () {
            $prices = Clinic::prices();

            return Service::query()
                ->with('media')
                ->get()
                ->map(function ($item) use ($prices) {
                    /** @var \App\Models\Service $item */
                    $item->setAttribute('prices', data_get(collect($prices)->firstWhere('uid', $item->uuid), 'items', []));

                    return $item;
                })
                ->values();
        });
    }


    public function getDoctorsAttribute()
    {
        if (!($this->type === BlockType::DOCTORS_ALT) || !($this->payload['doctors'] ?? false)) {
            return null;
        }

        $doctorIds = $this->payload['doctors'];

        return Doctors::getDoctors()->whereIn('id', $doctorIds);
    }

    public function getReviewsAttribute(): Collection|null
    {
        if ($this->type !== BlockType::REVIEWS || !($this->payload['reviews'] ?? false)) {
            return null;
        }

        $reviewIds = $this->payload['reviews'];
        $cacheKey = 'block_reviews_' . md5(implode(',', $reviewIds));

        return Cache::remember($cacheKey, 3600, function () use ($reviewIds) {
            return Review::with(['doctor', 'pages'])->whereIn('id', $reviewIds)->get();
        });
    }

    public function getReviewsAltAttribute(): Collection|null
    {
        $isHome = request()->is('/');
        $reviews = Cache::remember('reviews', 2592000, fn() => Review::with(['doctor', 'pages'])->get());

        if ($this->type !== BlockType::REVIEWS_ALT) {
            return null;
        }

        if ($isHome) {
            // Композитная сортировка: сначала is_home, потом get_date
            $sorted = $reviews->sortByDesc(function ($item) {
                return [$item->is_home, $item->get_date];
            });
            return $sorted->slice(0, 12)->values();
        } else {
            return $reviews->sortByDesc('get_date')->slice(0, 12)->values();
        }
    }

    public function getAuthorAttribute()
    {
        if ($this->type !== BlockType::AUTHOR || !($this->payload['author'] ?? false)) {
            return null;
        }

        $authorId = $this->payload['author'];
        $cityService = app(\App\Services\CityService::class);
        $slug = $cityService->getCurrentCity()?->slug ?? 'global';
        $cacheKey = 'block_author_' . $slug . '_' . $authorId;

        return Cache::remember($cacheKey, 3600, function () use ($authorId) {
            return Doctors::getDoctors()->where('id', $authorId)->first();
        });
    }

    public function getLicensesAttribute(): array|null
    {
        $settings = app(GeneralSettings::class);

        return $this->type === BlockType::LICENSES && $settings->licenses
            ? collect($settings->licenses)
                ->map(function ($item) {
                    $file = new File(storage_path('app/public/' . $item));

                    return [
                        'src' => "/storage/$item",
                        'thumb' => "/storage/$item",
                        'type' => $file->getMimeType(),
                    ];
                })
                ->values()
                ->toArray()
            : null;
    }

    public function getDocuments($item)
    {
        if($this->type === BlockType::UNIVERSAL_TEXT_BLOCK && isset($item['document'])){
            $file = new File(storage_path('app/public/' . $item['document']));
            return [
                'src' => "/storage/{$item['document']}",
                'thumb' => "/storage/{$item['document']}",
                'type' => $file->getMimeType(),
            ];
        }

        return null;
    }

    public function getElementsAttribute()
    {
        return ($this->type === BlockType::LIST_WITH_IMAGE || BlockType::ELEMENTS_ITEM_ROW || $this->type === BlockType::ELEMENTS_ITEM_COLUMN || $this->type === BlockType::CARDS_ITEM_ROW || $this->type === BlockType::CARDS_BORDER || $this->type === BlockType::NIGHT_LENSES_PICTURES) && isset($this->payload['elements'])
            ? collect($this->payload['elements'])
                ->map(function ($item) {
                    $item['responsive_image'] = null;
                    if (isset($item['media_collection'])) {
                        $responsiveImage = $this->getResponsiveImage($item['media_collection'], $item['title'], 'main');
                        $item['responsive_image'] = $responsiveImage;
                        $item['image_html'] = $responsiveImage?->toHtml();
                    }
                    $item['has_extra_info'] = $item['body_html']
                        || !empty($item['has_price'])
                        || !empty($item['has_an_appointment']);

                    return $item;
                })
                ->values()
                ->toArray()
            : [];
    }

    public function getServicesAttribute(): Collection|array
    {
        if (!isset($this->payload['services'])) {
            return [];
        }

        return collect($this->payload['services'])
            ->map(function ($item) {
                $item['responsive_image'] = null;
                if (isset($item['media_collection'])) {
                    $responsiveImage = $this->getResponsiveImage($item['media_collection'], $item['title'], 'main');
                    $item['responsive_image'] = $responsiveImage;
                    $item['image_html'] = $responsiveImage?->toHtml();
                }

                return $item;
            })
            ->values()
            ->toArray();
    }

    public function getPostsAttribute(): Collection|null
    {
        if ($this->type !== BlockType::CARDS_SLIDER || !($this->payload['is_blog'] ?? false)) {
            return null;
        }

        return Cache::remember('blog_posts_for_slider', 3600, function () {
            return Page::query()
                ->where('type', '=', PageType::Posts)
                ->where('active', '=', 1)
                ->with(['tags', 'media', 'category'])
                ->get();
        });
    }

    public function getPromotionsAttribute(): Collection|null
    {
        if ($this->type !== BlockType::PROMOTIONS) {
            return null;
        }

        $cityService = app(\App\Services\CityService::class);
        $slug = $cityService->getCurrentCity()?->slug ?? 'global';

        return Cache::remember('active_promotions_' . $slug, 3600, function () {
            return Promotion::query()
                ->where('archived', 0)
                ->with('media')
                ->get();
        });
    }

    public function paragraphs(): Attribute
    {
        return Attribute::make(function () {
            preg_match_all('/<p>(.*?)<\/p>/s', $this->body_html, $matches);

            return $matches[0];
        })->shouldCache();
    }

    public function bodyHtmlParts(): Attribute
    {
        return Attribute::make(get: function () {
            $paragraphs = $this->paragraphs;

            if (!count($paragraphs)) {
                return ['', null];
            }

            if (count($paragraphs) < 4) {
                return [implode('', array_splice($paragraphs, 0, 3)), null];
            }

            return [implode('', array_splice($paragraphs, 0, 3)), implode('', array_slice($this->paragraphs, 3))];

        })->shouldCache();
    }

    public function elementToSpanWrap($element): string
    {
        return preg_replace('/(.*?)(?:\()(.*?)(?:\))/', '$1<span class="text-interactive/50">($2)</span>', $element);
    }
}
