<?php

namespace App\Models;

use App\Enums\ResourcesForReviews;
use App\Models\Traits\Filterable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $service_uuid
 * @property string $name
 * @property string $body
 * @property numeric $rating
 */
class Review extends Model
{
    use HasFactory, Filterable;

    protected static function booted()
    {
        static::saved(function () {
            \Illuminate\Support\Facades\Cache::forget('reviews');
        });

        static::deleted(function () {
            \Illuminate\Support\Facades\Cache::forget('reviews');
        });
    }


    protected $fillable = [
        'name',
        'body_html',
        'rating',
        'resource',
        'get_date',
        'link_resource',
        'is_home',
    ];

    public function body(): Attribute
    {
        return Attribute::make(get: fn() => strip_tags($this->body_html))
            ->shouldCache();
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(Page::class);
    }

    public function getDoctorInitialsAttribute()
    {
        if (!$this->doctor) {
            return '';
        }
        
        $name = $this->doctor->name ?? '';
        $surname = $this->doctor->surname ?? '';
        return $surname .' '. mb_substr(explode(' ', $name)[0], 0, 1) .'.';
    }

    public function getResourcesAttribute()
    {
        return [ResourcesForReviews::toArray(), ResourcesForReviews::icons()];
    }
}
