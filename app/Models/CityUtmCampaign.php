<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CityUtmCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'city_id',
        'source_id',
        'medium',
        'medium_name',
        'phone_id',
        'open_booking_widget',
        'started_at',
        'stopped_at',
        'archived_at',
        'restarted_from_id',
    ];

    protected $casts = [
        'open_booking_widget' => 'boolean',
        'started_at' => 'immutable_datetime',
        'stopped_at' => 'immutable_datetime',
        'archived_at' => 'immutable_datetime',
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

    public function restartedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'restarted_from_id');
    }
}
