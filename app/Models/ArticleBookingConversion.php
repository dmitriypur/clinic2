<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleBookingConversion extends Model
{
    protected $fillable = [
        'page_id',
        'city_id',
        'event_uuid',
        'page_url',
        'page_path',
        'entry_point',
        'booking_mode',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
