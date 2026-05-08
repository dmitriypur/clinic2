<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ArticleImport;
use App\Services\ArticleImport\ArticleImportException;
use App\Services\ArticleImport\ArticleImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ImportArticle implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;

    public int $tries = 2;

    public function __construct(public int $articleImportId)
    {
    }

    public function handle(ArticleImportService $articleImportService): void
    {
        $articleImport = ArticleImport::query()->findOrFail($this->articleImportId);

        $articleImport->update([
            'status' => ArticleImport::STATUS_PROCESSING,
            'started_at' => now(),
            'error_message' => null,
        ]);

        $result = $articleImportService->import($articleImport->payload);

        $articleImport->update([
            'status' => ArticleImport::STATUS_COMPLETED,
            'page_id' => $result->page->id,
            'warnings' => $result->warnings,
            'completed_at' => now(),
        ]);
    }

    public function failed(Throwable $exception): void
    {
        $articleImport = ArticleImport::query()->find($this->articleImportId);

        if (! $articleImport) {
            return;
        }

        $message = $exception instanceof ArticleImportException
            ? $exception->userMessage()
            : $exception->getMessage();

        $articleImport->update([
            'status' => ArticleImport::STATUS_FAILED,
            'error_message' => $message,
            'completed_at' => now(),
        ]);
    }
}
