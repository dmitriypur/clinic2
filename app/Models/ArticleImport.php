<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleImport extends Model
{
    use HasFactory;

    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'staff_id',
        'page_id',
        'status',
        'document_url',
        'payload',
        'warnings',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'warnings' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_QUEUED => 'В очереди',
            self::STATUS_PROCESSING => 'В работе',
            self::STATUS_COMPLETED => 'Готово',
            self::STATUS_FAILED => 'Ошибка',
        ];
    }

    public static function statusColors(): array
    {
        return [
            self::STATUS_QUEUED => 'gray',
            self::STATUS_PROCESSING => 'warning',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_FAILED => 'danger',
        ];
    }
}
