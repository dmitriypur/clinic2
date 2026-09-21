<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasActiveOrderedScope
{
    public function scopeActiveOrdered(Builder $query): Builder
    {
        return $query
            ->where($this->qualifyColumn('is_active'), true)
            ->orderBy($this->qualifyColumn('sort_order'))
            ->orderBy($this->qualifyColumn($this->getKeyName()));
    }
}
