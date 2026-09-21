<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasActiveOrderedScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FrameBrand extends Model
{
    use HasActiveOrderedScope;
    use HasFactory;

    protected $fillable = [
        'name',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function frames(): HasMany
    {
        return $this->hasMany(Frame::class, 'brand_id');
    }
}
