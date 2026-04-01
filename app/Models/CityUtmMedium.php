<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CityUtmMedium extends Model
{
    use HasFactory;

    protected $table = 'city_utm_mediums';

    protected $fillable = [
        'city_id',
        'source_id',
        'medium',
        'medium_name',
        'phone_id',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'immutable_date',
        'end_date' => 'immutable_date',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(CityUtmSource::class, 'source_id');
    }

    public function phone(): BelongsTo
    {
        return $this->belongsTo(CityUtmPhone::class, 'phone_id');
    }
}
