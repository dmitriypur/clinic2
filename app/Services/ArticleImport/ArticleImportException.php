<?php

declare(strict_types=1);

namespace App\Services\ArticleImport;

use RuntimeException;
use Throwable;

class ArticleImportException extends RuntimeException
{
    public function __construct(
        string $stage,
        string $message,
        private readonly ?string $hint = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, previous: $previous);

        $this->stage = $stage;
    }

    private readonly string $stage;

    public function stage(): string
    {
        return $this->stage;
    }

    public function hint(): ?string
    {
        return $this->hint;
    }

    public function userMessage(): string
    {
        $lines = [
            'Этап: ' . $this->stage(),
            'Причина: ' . $this->getMessage(),
        ];

        if ($this->hint() !== null && $this->hint() !== '') {
            $lines[] = 'Что проверить: ' . $this->hint();
        }

        return implode("\n", $lines);
    }
}
