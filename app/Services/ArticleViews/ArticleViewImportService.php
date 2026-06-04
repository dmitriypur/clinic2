<?php

namespace App\Services\ArticleViews;

use App\Enums\PageType;
use App\Models\ArticleViewCounter;
use App\Models\Page;
use RuntimeException;

class ArticleViewImportService
{
    public function __construct(
        private readonly ArticleViewCounterService $counterService,
    ) {}

    public function import(string $csvPath): ArticleViewImportResult
    {
        if (! is_readable($csvPath)) {
            throw new RuntimeException("CSV-файл недоступен для чтения: {$csvPath}");
        }

        $result = new ArticleViewImportResult();
        $file = new \SplFileObject($csvPath, 'rb');
        $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
        $file->setCsvControl(';');

        $headers = null;

        foreach ($file as $row) {
            if (! is_array($row) || $row === [null]) {
                continue;
            }

            $row = array_map(fn ($value): string => trim((string) $value), $row);

            if ($headers === null) {
                $headers = $this->normalizeHeaders($row);
                continue;
            }

            $data = $this->combineRow($headers, $row);
            $url = $data['Адрес страницы'] ?? '';
            $views = $this->parseViews($data['Просмотры'] ?? '');
            $pagePath = $this->counterService->normalizeArticlePath($url);

            if ($pagePath === null || $views === null) {
                $result->skipped++;
                continue;
            }

            $handle = $this->counterService->handleFromPath($pagePath);

            if ($handle === null) {
                $result->skipped++;
                continue;
            }

            $page = Page::withoutGlobalScopes()
                ->where('handle', $handle)
                ->where('type', PageType::Posts)
                ->where('active', true)
                ->first();

            $counter = ArticleViewCounter::query()->firstOrNew([
                'page_path_hash' => $this->counterService->hashPath($pagePath),
            ]);
            $isNew = ! $counter->exists;

            $counter->fill([
                'page_path' => $pagePath,
                'handle' => $handle,
                'page_id' => $page?->id,
                'views_count' => $views,
                'source' => 'yandex_csv',
            ])->save();

            $isNew ? $result->created++ : $result->updated++;
            $page ? $result->linked++ : $result->missingLocalPage++;
        }

        return $result;
    }

    private function normalizeHeaders(array $headers): array
    {
        return array_map(
            fn (string $header): string => preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header,
            $headers,
        );
    }

    private function combineRow(array $headers, array $row): array
    {
        $row = array_pad($row, count($headers), '');

        return array_combine($headers, array_slice($row, 0, count($headers))) ?: [];
    }

    private function parseViews(string $value): ?int
    {
        $normalized = preg_replace('~[^\d]~u', '', $value);

        if (! is_string($normalized) || $normalized === '') {
            return null;
        }

        return (int) $normalized;
    }
}
