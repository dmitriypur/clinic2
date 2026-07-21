<?php

namespace App\Models;

use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteSearchQuery extends Model
{
    use MassPrunable;

    protected $fillable = [
        'query',
        'city_id',
        'results_count',
    ];

    protected $casts = [
        'city_id' => 'integer',
        'results_count' => 'integer',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function prunable()
    {
        return static::query()->where('created_at', '<', now()->subDays(90));
    }
}
