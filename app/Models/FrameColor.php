<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasActiveOrderedScope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FrameColor extends Model
{
    use HasActiveOrderedScope;
    use HasFactory;

    protected $fillable = [
        'name',
        'hex',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function frames(): BelongsToMany
    {
        return $this->belongsToMany(Frame::class, 'frame_color');
    }

    protected function hex(): Attribute
    {
        return Attribute::make(
            set: static fn (string $value): string => strtoupper($value),
        );
    }
}
