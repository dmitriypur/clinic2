<?php

declare(strict_types=1);

namespace App\Services\ArticleImport;

use App\Models\Page;

class ArticleImportResult
{
    public function __construct(
        public readonly Page $page,
        public readonly array $warnings = [],
    ) {}

    public function hasWarnings(): bool
    {
        return $this->warnings !== [];
    }
}
