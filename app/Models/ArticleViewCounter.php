<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleViewCounter extends Model
{
    protected $fillable = [
        'page_path',
        'page_path_hash',
        'handle',
        'page_id',
        'views_count',
        'source',
    ];

    protected $casts = [
        'views_count' => 'integer',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
