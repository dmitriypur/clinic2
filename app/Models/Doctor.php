<?php

namespace App\Models;

use App\Jobs\RegenerateSitemap;
use App\Models\Traits\HasCityScope;
use App\Models\Traits\HasSafeMediaConversions;
use App\Support\DoctorAge;
use App\Support\CitySeoVariables;
use App\Settings\SeoSettings;
use App\Services\BookingWidgetCacheVersionService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
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
 * @property string $name
 * @property string $surname
 * @property string $speciality
 * @property string $job_title
 * @property string $bio
 * @property string $excerpt
 * @property ?string $actual_video_url
 * @property string $full_name
 * @property ?HtmlableMedia $avatar_image
 */
class Doctor extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, HasUlids, HasSlug, HasCityScope, HasSafeMediaConversions;


    protected $dispatchesEvents = [
        'created' => RegenerateSitemap::class,
        'updated' => RegenerateSitemap::class,
    ];

    protected $fillable = [
        'uuid',
        'name',
        'surname',
        'speciality',
        'job_title',
        'page_sort_order',
        'excerpt',
        'bio',
        'video_url',
        'extra',
        'seo',
        'handle',
    ];

    protected $casts = [
        'seo' => 'json',
        'extra' => 'array',
    ];

    public function scopePubliclyVisible(Builder $query): Builder
    {
        if ($query->getConnection()->getDriverName() === 'sqlite') {
            return $query->whereRaw(
                "LOWER(COALESCE(JSON_EXTRACT(`seo`, '$.\"noindex\"'), 'false')) NOT IN ('true', '1')"
            );
        }

        return $query->whereRaw(
            "LOWER(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`seo`, '$.\"noindex\"')), 'false')) NOT IN ('true', '1')"
        );
    }

    public function scopeOrderedForPublicIndex(Builder $query): Builder
    {
        return $query
            ->orderByRaw('doctors.page_sort_order IS NULL, doctors.page_sort_order ASC')
            ->orderBy('doctors.id');
    }

    protected static function booted()
    {
        static::saved(function () {
            self::clearDoctorsCache();
        });

        static::deleted(function () {
            self::clearDoctorsCache();
        });
    }

    private static function clearDoctorsCache(): void
    {
        app(BookingWidgetCacheVersionService::class)->bump();

        Cache::forget('doctors'); // Clear old cache just in case
        Cache::forget('doctors-all');

        $cityService = app(\App\Services\CityService::class);
        // Получаем слаги всех активных городов + 'global'
        $slugs = $cityService->getActiveCities()->pluck('slug')->push('global');

        foreach ($slugs as $slug) {
            Cache::forget("doctors-{$slug}");
            
            // Очистка кеша страниц врачей (предполагаем максимум 20 страниц)
            for ($i = 1; $i <= 20; $i++) {
                Cache::forget("doctors-page-{$slug}-{$i}");
            }
        }
    }

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function cities(): BelongsToMany
    {
        return $this->belongsToMany(City::class);
    }

    public function reviews(): hasMany
    {
        return $this->hasMany(Review::class);
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('surname')
            ->saveSlugsTo('handle')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('main')
            ->nonQueued()
            ->withResponsiveImages()
            ->width(400)
            ->height(400)
            ->format(Manipulations::FORMAT_WEBP)
            ->performOnCollections('default');

        $this->addMediaConversion('main-png')
            ->withResponsiveImages()
            ->format(Manipulations::FORMAT_PNG)
            ->fit(Manipulations::FIT_CROP, 334, 400)
            ->performOnCollections('default');

        $this->addMediaConversion('thumb')
            ->withResponsiveImages()
            ->format(Manipulations::FORMAT_WEBP)
            ->fit(Manipulations::FIT_CROP, 1176, 550)
            ->performOnCollections('documents');
    }

    public function fullName(): Attribute
    {
        return Attribute::get(fn() => $this->surname . ' ' . $this->name);
    }

    public function doctorInitials(): Attribute
    {
        return Attribute::get(fn() => $this->surname . ' ' . mb_substr(explode(' ', $this->name)[0], 0, 1) .'.');
    }

    public function getAvatarImageAttribute(): ?HtmlableMedia
    {
        if (!$this->hasMedia()) {
            return null;
        }

        $settings = app(SeoSettings::class);

        return $this->safeMediaImage($this->getFirstMedia(), 'main')
            ->attributes([
                'alt' => Str::of($settings->image_alt_template)
                    ->replace('{h1}', $this->full_name)
                    ->value(),
                'title' => Str::of($settings->image_title_template)
                    ->replace('{h1}', $this->full_name)
                    ->value(),
            ]);
    }

    public function url(): Attribute
    {
        return Attribute::get(
            fn() => city_route('doctor.show', ['handle' => $this->handle ?? $this->id])
        )->shouldCache();
    }

    public function extraInformationFilled(): Attribute
    {
        return Attribute::get(
            fn() => collect([
                    data_get($this->extra, 'seniority'),
                    data_get($this->extra, 'category'),
                    $this->receives_display,
                    data_get($this->extra, 'education'),
                    data_get($this->extra, 'professional_development'),
                    count((array) data_get($this->extra, 'skills', [])),
                ])
                    ->filter()
                    ->count() > 0
        )->shouldCache();
    }

    public function ageMinMonths(): Attribute
    {
        return Attribute::get(fn() => DoctorAge::minMonths($this->extra ?? []))->shouldCache();
    }

    public function ageMaxMonths(): Attribute
    {
        return Attribute::get(fn() => DoctorAge::maxMonths($this->extra ?? []))->shouldCache();
    }

    public function receivesText(): Attribute
    {
        return Attribute::get(fn() => DoctorAge::receivesText($this->extra ?? []))->shouldCache();
    }

    public function receivesDisplay(): Attribute
    {
        return Attribute::get(fn() => DoctorAge::display($this->extra ?? []))->shouldCache();
    }

    public function getActualVideoUrlAttribute(): ?string
    {
        if (!$this->video_url) {
            return null;
        }

        if (Str::contains($this->video_url, 'vk.com')) {
            return 'https://vk.com/video_ext.php?oid=' .
                Str::of($this->video_url)
                    ->between('https://vk.com/video', '?')
                    ->replace('_', '&id=') .
                '&hd=2&autoplay=1';
        }

        return Str::contains($this->video_url, 'youtu.be')
            ? 'https://www.youtube.com/embed/' .
            Str::afterLast($this->video_url, '/') .
            '?autoplay=1'
            : $this->video_url;
    }

    public function withResolvedCitySeoVariables(): self
    {
        $doctor = clone $this;
        $replacer = app(CitySeoVariables::class);

        $doctor->speciality = $replacer->replace((string) $doctor->speciality) ?? '';
        $doctor->job_title = $replacer->replace($doctor->job_title);
        $doctor->excerpt = $replacer->replace($doctor->excerpt);
        $doctor->bio = $replacer->replace($doctor->bio);

        $seo = (array) ($doctor->seo ?? []);
        $seo['title'] = $replacer->replace($seo['title'] ?? null);
        $seo['description'] = $replacer->replace($seo['description'] ?? null);
        $doctor->seo = $seo;

        return $doctor;
    }
}
