<?php

namespace App\Models;

use App\Jobs\RegenerateSitemap;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Category extends Model
{
    use HasFactory, HasSlug;

    protected $dispatchesEvents = [
        'created' => RegenerateSitemap::class,
        'updated' => RegenerateSitemap::class,
    ];

    protected $fillable = [
        'title',
        'body_html',
        'handle',
        'seo',
        'parent_id'
    ];

    protected $casts = [
        'seo' => 'json',
    ];

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('handle')
            ->doNotGenerateSlugsOnUpdate();
    }
}
