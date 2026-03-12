<?php

declare(strict_types=1);

namespace App\Services\ArticleImport;

use Illuminate\Support\Facades\Http;

class GoogleDocsFetcher
{
    public function fetch(string $url): string
    {
        $documentId = $this->extractDocumentId($url);
        $exportUrl = "https://docs.google.com/document/d/{$documentId}/export?format=html";

        $response = Http::timeout(20)
            ->withHeaders([
                'User-Agent' => 'ZrenieClinic Article Importer',
            ])
            ->get($exportUrl);

        if ($response->failed()) {
            throw new ArticleImportException(
                'загрузка Google Docs',
                'Не удалось загрузить документ Google Docs.',
                'Проверьте, что документ доступен по ссылке без авторизации и ссылка ведет именно на Google Docs.'
            );
        }

        $html = $response->body();

        if (trim($html) === '') {
            throw new ArticleImportException(
                'загрузка Google Docs',
                'Google Docs вернул пустой документ.',
                'Проверьте, что в документе есть контент и он не закрыт от просмотра.'
            );
        }

        return $html;
    }

    private function extractDocumentId(string $url): string
    {
        if (preg_match('~/document/d/([a-zA-Z0-9_-]+)~', $url, $matches) !== 1) {
            throw new ArticleImportException(
                'подготовка ссылки',
                'Ссылка на Google Docs должна содержать идентификатор документа.',
                'Используйте ссылку вида https://docs.google.com/document/d/...'
            );
        }

        return $matches[1];
    }
}
