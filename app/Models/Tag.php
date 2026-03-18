<?php

namespace App\Models;

use App\Support\CitySeoVariables;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Tag extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'title',
        'handle',
        'seo'
    ];

    protected $casts = [
        'seo' => 'json',
    ];

    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(Page::class);
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('handle')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function withResolvedCitySeoVariables(): self
    {
        $tag = clone $this;
        $replacer = app(CitySeoVariables::class);

        $tag->title = $replacer->replace((string) $tag->title) ?? '';

        $seo = (array) ($tag->seo ?? []);
        $seo['title'] = $replacer->replace($seo['title'] ?? null);
        $seo['description'] = $replacer->replace($seo['description'] ?? null);
        $tag->seo = $seo;

        return $tag;
    }
}
