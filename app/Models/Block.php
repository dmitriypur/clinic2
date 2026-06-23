<?php

namespace App\Models;

use App\Clinic;
use App\Enums\BlockType;
use App\Enums\PageType;
use App\Helpers\Doctors;
use App\Models\Doctor;
use App\Models\Traits\HasCityScope;
use App\Models\Traits\HasSafeMediaConversions;
use App\Settings\GeneralSettings;
use App\Settings\SeoSettings;
use App\Support\CitySeoVariables;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
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
 * @property array $price_list_items
 * @property bool $title_hidden
 */
class Block extends Model implements HasMedia, Sortable
{
    use HasFactory, InteractsWithMedia, SortableTrait, HasCityScope, HasSafeMediaConversions;

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

        static::saving(function (Block $block): void {
            if ($block->type !== BlockType::ARTICLE_NAVIGATION) {
                return;
            }

            $page = $block->page()->withoutGlobalScopes()->first();

            if ($page?->type !== PageType::Posts) {
                throw ValidationException::withMessages([
                    'type' => 'Блок навигации можно добавить только к статье.',
                ]);
            }

            $duplicateExists = static::query()
                ->withoutGlobalScopes()
                ->where('page_id', $block->page_id)
                ->where('type', BlockType::ARTICLE_NAVIGATION)
                ->when($block->exists, fn ($query) => $query->whereKeyNot($block->getKey()))
                ->exists();

            if ($duplicateExists) {
                throw ValidationException::withMessages([
                    'type' => 'У статьи уже есть блок навигации.',
                ]);
            }
        });

    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('main')
            ->width(400)
            ->height(400)
            ->format(Manipulations::FORMAT_WEBP)
            ->withResponsiveImages();

        $this->addMediaConversion('apparatus')
            ->width(1200)
            ->format(Manipulations::FORMAT_WEBP)
            ->withResponsiveImages();

        $this->addMediaConversion('hero')
            ->width(1920)
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

        return $this->safeMediaImage($this->getFirstMedia($collection), $conversion)?->attributes([
            'alt' => Str::of($settings->image_alt_template)->replace('{h1}', $title)->value(),
            'title' => Str::of($settings->image_title_template)->replace('{h1}', $title)->value(),
        ]);
    }

    public function getImageAltText(string $title): string
    {
        $settings = app(SeoSettings::class);

        return Str::of($settings->image_alt_template)->replace('{h1}', $title)->value();
    }

    public function getImageTitleText(string $title): string
    {
        $settings = app(SeoSettings::class);

        return Str::of($settings->image_title_template)->replace('{h1}', $title)->value();
    }

    public function getImageUrl(string $collection): string
    {
        return $this->getFirstMediaUrl($collection);
    }

    public function withResolvedCitySeoVariables(): self
    {
        $block = clone $this;
        $replacer = app(CitySeoVariables::class);

        $block->title = $replacer->replace((string) $block->title) ?? '';
        $block->body_html = $replacer->replace($block->body_html);
        $block->payload = $this->resolveCitySeoVariablesInValue($block->payload, $replacer);

        return $block;
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

    public function getImagePositionAttribute(): string
    {
        return data_get($this->payload, 'image_position', 'right');
    }

    public function getHasImageAttribute(): bool
    {
        return $this->getFirstMedia('default') !== null && $this->image_position !== 'none';
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

    public function getPriceListItemsAttribute(): array
    {
        return collect($this->prices ?? [])
            ->map(function (array $price) {
                [$title, $description] = $this->splitPriceListItem($price['item'] ?? '');

                return [
                    'title' => $title,
                    'description' => $description,
                    'price' => $price['price1'] ?? null,
                    'old_price' => ($price['price2'] ?? 0) > 0 ? $price['price2'] : null,
                    'price_from' => (bool) ($price['price_from'] ?? false),
                ];
            })
            ->filter(fn (array $price) => filled($price['title']) && filled($price['price']))
            ->values()
            ->toArray();
    }

    private function splitPriceListItem(string $item): array
    {
        $item = trim($item);

        if (preg_match('/^(.*?)\s*(<br\s*\/?>|\R)\s*(.+)$/iu', $item, $matches)) {
            return [trim(strip_tags($matches[1])), trim(strip_tags($matches[3]))];
        }

        if (preg_match('/^(.+?)\s*(\([^()]+\))$/u', $item, $matches)) {
            return [trim(strip_tags($matches[1])), trim(strip_tags($matches[2]))];
        }

        return [trim(strip_tags($item)), null];
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
        if ($this->type !== BlockType::DOCTORS_ALT) {
            return null;
        }

        $doctors = Doctors::getDoctors();
        $excludedDoctorIds = collect($this->payload['excluded_doctors'] ?? [])
            ->filter()
            ->values();

        if ($excludedDoctorIds->isNotEmpty()) {
            return $doctors
                ->reject(fn (Doctor $doctor) => $excludedDoctorIds->contains($doctor->id))
                ->values();
        }

        $doctorIds = collect($this->payload['doctors'] ?? [])->filter()->values();

        // Поддержка старых блоков: если раньше был задан whitelist врачей,
        // сохраняем это поведение, пока блок не будет пересохранен в новом режиме исключений.
        if ($doctorIds->isNotEmpty()) {
            $selectedDoctors = $doctors->whereIn('id', $doctorIds->all())->values();
            if ($selectedDoctors->isNotEmpty()) {
                return $selectedDoctors;
            }
        }

        return $doctors->values();
    }

    public function getReviewsAttribute(): Collection|null
    {
        if ($this->type !== BlockType::REVIEWS || !($this->payload['reviews'] ?? false)) {
            return null;
        }

        $reviewIds = $this->payload['reviews'];
        $citySlug = app(\App\Services\CityService::class)->getCurrentCity()?->slug ?? 'global';
        $cacheKey = 'block_reviews_' . $citySlug . '_' . md5(implode(',', $reviewIds));

        return Cache::remember($cacheKey, 3600, function () use ($reviewIds) {
            return Review::with(['doctor', 'pages'])->whereIn('id', $reviewIds)->get();
        });
    }

    public function getReviewsAltAttribute(): Collection|null
    {
        $isHome = request()->is('/');
        if ($this->type !== BlockType::REVIEWS_ALT) {
            return null;
        }

        $citySlug = app(\App\Services\CityService::class)->getCurrentCity()?->slug ?? 'global';
        $cacheKey = "reviews_with_cities_{$citySlug}";
        $reviews = Cache::remember($cacheKey, 2592000, fn() => Review::with(['doctor', 'pages'])->get());

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
        if ($this->type !== BlockType::AUTHOR && $this->type !== BlockType::EXPERT_OPINION || !($this->payload['author'] ?? false)) {
            return null;
        }

        $authorId = $this->payload['author'];
        $cityService = app(\App\Services\CityService::class);
        $slug = $cityService->getCurrentCity()?->slug ?? 'global';
        $cacheKey = 'block_author_' . $slug . '_' . $authorId;

        return Cache::remember($cacheKey, 3600, function () use ($authorId) {
            $author = Doctors::getDoctors()->firstWhere('id', $authorId);

            if ($author) {
                return $author;
            }

            return Doctor::query()
                ->withoutGlobalScope('city')
                ->publiclyVisible()
                ->with('media')
                ->find($authorId);
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
        return in_array($this->type, [
            BlockType::LIST_WITH_IMAGE,
            BlockType::ELEMENTS_ITEM_ROW,
            BlockType::ELEMENTS_ITEM_COLUMN,
            BlockType::CARDS_ITEM_ROW,
            BlockType::CARDS_BORDER,
            BlockType::NIGHT_LENSES_PICTURES,
        ], true) && isset($this->payload['elements'])
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

    public function getApparatusTreatmentSectionsAttribute(): array
    {
        if ($this->type !== BlockType::APPARATUS_TREATMENT || !isset($this->payload['sections'])) {
            return [];
        }

        return collect($this->payload['sections'])
            ->map(function ($item) {
                $item['responsive_image'] = null;
                $item['image_html'] = null;

                if (isset($item['media_collection'])) {
                    $responsiveImage = $this->getResponsiveImage($item['media_collection'], $item['title'] ?? $this->title, 'apparatus');
                    $item['responsive_image'] = $responsiveImage;
                    $item['image_html'] = $responsiveImage?->toHtml();
                }

                return $item;
            })
            ->values()
            ->toArray();
    }

    public function getApparatusMethodsItemsAttribute(): array
    {
        if ($this->type !== BlockType::APPARATUS_METHODS || !isset($this->payload['items'])) {
            return [];
        }

        return collect($this->payload['items'])
            ->map(function ($item) {
                $item['responsive_image'] = null;
                $item['image_html'] = null;

                if (isset($item['media_collection'])) {
                    $responsiveImage = $this->getResponsiveImage($item['media_collection'], $item['title'] ?? $this->title, 'apparatus');
                    $item['responsive_image'] = $responsiveImage;
                    $item['image_html'] = $responsiveImage?->toHtml();
                }

                return $item;
            })
            ->values()
            ->toArray();
    }

    public function getDiagnosticMethodsItemsAttribute(): array
    {
        if (!in_array($this->type, [BlockType::DIAGNOSTIC_METHODS, BlockType::TREATMENT_METHODS], true) || !isset($this->payload['items'])) {
            return [];
        }

        return collect($this->payload['items'])
            ->map(function ($item) {
                $item['responsive_image'] = null;
                $item['image_html'] = null;
                $item['href'] = !empty($item['link']) ? city_url($item['link']) : null;

                if (isset($item['media_collection'])) {
                    $responsiveImage = $this->getResponsiveImage($item['media_collection'], $item['title'] ?? $this->title, 'main');
                    $item['responsive_image'] = $responsiveImage;
                    $item['image_html'] = $responsiveImage?->toHtml();
                }

                return $item;
            })
            ->filter(fn ($item) => !empty($item['title']) || !empty($item['body_html']))
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

    private function resolveCitySeoVariablesInValue(mixed $value, CitySeoVariables $replacer): mixed
    {
        if (is_string($value)) {
            return $replacer->replace($value);
        }

        if (!is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $nestedValue) {
            $value[$key] = $this->resolveCitySeoVariablesInValue($nestedValue, $replacer);
        }

        return $value;
    }
}
