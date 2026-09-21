<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FrameGender;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Frame extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'curator_media_id',
        'model',
        'description',
        'genders',
        'sort_order',
        'is_hit',
        'is_school_choice',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_hit' => 'boolean',
        'is_school_choice' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(FrameBrand::class, 'brand_id');
    }

    public function curatorMedia(): BelongsTo
    {
        return $this->belongsTo(CuratorMedia::class, 'curator_media_id');
    }

    public function colors(): BelongsToMany
    {
        return $this->belongsToMany(FrameColor::class, 'frame_color');
    }

    public function ageGroups(): BelongsToMany
    {
        return $this->belongsToMany(FrameAgeGroup::class, 'frame_age_group');
    }

    public function scopePublicCatalog(Builder $query): Builder
    {
        return $query
            ->where($this->qualifyColumn('is_active'), true)
            ->orderBy($this->qualifyColumn('sort_order'))
            ->orderBy($this->qualifyColumn($this->getKeyName()));
    }

    public function genderLabel(): string
    {
        if (count($this->genders) !== 1) {
            return 'Любой пол';
        }

        return FrameGender::from($this->genders[0])->cardLabel();
    }

    protected function genders(): Attribute
    {
        return Attribute::make(
            get: static function (?string $value): array {
                $decoded = json_decode($value ?? '[]', true);

                return is_array($decoded) ? $decoded : [];
            },
            set: static function ($value): string {
                $genders = collect(is_array($value) ? $value : [])
                    ->filter(fn ($gender): bool => in_array($gender, FrameGender::values(), true))
                    ->unique()
                    ->values()
                    ->all();

                return json_encode($genders, JSON_THROW_ON_ERROR);
            },
        );
    }
}
