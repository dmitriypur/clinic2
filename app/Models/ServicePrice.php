<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicePrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'city_id',
        'price',
        'old_price',
        'price_from',
    ];

    protected $casts = [
        'price_from' => 'boolean',
    ];

    protected static function booted()
    {
        static::saved(function () {
            Service::clearServicesCache();
        });

        static::deleted(function () {
            Service::clearServicesCache();
        });
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
