<?php

namespace App\Models;

use App\Settings\SeoSettings;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\HtmlableMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property string $id
 * @property ?string $uuid
 * @property string $title
 * @property string $subtitle
 * @property string $description_html
 * @property bool $has_price
 * @property bool $has_an_appointment
 */
class Element extends Model implements HasMedia, Sortable
{
    use HasFactory, InteractsWithMedia, SortableTrait;

    protected $fillable = [
        'uuid',
        'has_price',
        'has_an_appointment',
        'title',
        'subtitle',
        'description_html',
    ];

    protected $casts = [
        'has_price' => 'bool',
        'has_an_appointment' => 'bool',
    ];

    protected $appends = [
        'has_extra_info',
        'image_html',
    ];

    protected $hidden = [
        'media',
    ];

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

    /**
     * @return BelongsTo
     */
    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
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

    public function getImageHtmlAttribute(): string
    {
        return $this->getResponsiveImageAttribute() ? $this->getResponsiveImageAttribute()->toHtml() : '';
    }

    public function getHasExtraInfoAttribute(): bool
    {
        return boolval($this->description_html) || $this->has_price || $this->has_an_appointment;
    }
}
