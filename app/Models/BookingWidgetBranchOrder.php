<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingWidgetBranchOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'city_id',
        'clinic_id',
        'clinic_name',
        'branch_id',
        'title',
        'sort_order',
    ];

    protected $casts = [
        'city_id' => 'integer',
        'clinic_id' => 'integer',
        'branch_id' => 'integer',
        'sort_order' => 'integer',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
