<?php

declare(strict_types=1);

namespace App\Services\ArticleImport;

use App\Models\Block;
use App\Models\Page;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class GoogleDriveImageImporter
{
    public function attachToBlock(Block $block, string $url): void
    {
        [$tempPath, $extension] = $this->downloadToTempFile($url);

        try {
            $block->addMedia($tempPath)
                ->usingFileName("article-block-{$block->id}.{$extension}")
                ->toMediaCollection('default');
        } finally {
            @unlink($tempPath);
        }
    }

    public function attachToPage(Page $page, string $url): void
    {
        [$tempPath, $extension] = $this->downloadToTempFile($url);

        try {
            $page->addMedia($tempPath)
                ->usingFileName("article-page-{$page->id}.{$extension}")
                ->toMediaCollection('default');
        } finally {
            @unlink($tempPath);
        }
    }

    public function attachStoredFileToBlock(
        Block $block,
        string $path,
        string $disk = 'local',
    ): void {
        if (! Storage::disk($disk)->exists($path)) {
            throw new ArticleImportException(
                'загрузка изображения эксперта',
                'Файл фотографии эксперта не найден.'
            );
        }

        $absolutePath = Storage::disk($disk)->path($path);
        $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'webp';

        try {
            $block->addMedia($absolutePath)
                ->usingFileName("article-expert-{$block->id}.{$extension}")
                ->toMediaCollection('default');
        } finally {
            Storage::disk($disk)->delete($path);
        }
    }

    private function makeDownloadUrl(string $url): string
    {
        if (preg_match('~/file/d/([a-zA-Z0-9_-]+)~', $url, $matches) !== 1) {
            return $url;
        }

        return "https://drive.google.com/uc?export=download&id={$matches[1]}";
    }

    private function detectExtension(?string $contentType): string
    {
        return match ($contentType) {
            'image/png' => 'png',
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/gif' => 'gif',
            default => 'webp',
        };
    }

    private function downloadToTempFile(string $url): array
    {
        $downloadUrl = $this->makeDownloadUrl($url);

        $response = Http::timeout(60)
            ->withHeaders([
                'User-Agent' => 'ZrenieClinic Article Importer',
            ])
            ->get($downloadUrl);

        if ($response->failed()) {
            throw new ArticleImportException(
                'загрузка изображений',
                'Не удалось загрузить изображение из Google Drive.',
                'Проверьте, что ссылка на файл рабочая и файл доступен по ссылке.'
            );
        }

        $extension = $this->detectExtension($response->header('Content-Type'));
        $tempPath = tempnam(sys_get_temp_dir(), 'article-import-image-');

        if ($tempPath === false) {
            throw new ArticleImportException(
                'подготовка изображений',
                'Не удалось создать временный файл для изображения.'
            );
        }

        file_put_contents($tempPath, $response->body());

        return [$tempPath, $extension];
    }
}
