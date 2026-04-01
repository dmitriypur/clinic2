<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CityUtmSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'city_id',
        'source',
        'name',
        'default_phone_id',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function defaultPhone(): BelongsTo
    {
        return $this->belongsTo(CityUtmPhone::class, 'default_phone_id');
    }

    public function mediums(): HasMany
    {
        return $this->hasMany(CityUtmMedium::class, 'source_id');
    }
}
