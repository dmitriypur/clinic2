<?php

namespace App\Services\ArticleViews;

class ArticleViewImportResult
{
    public function __construct(
        public int $created = 0,
        public int $updated = 0,
        public int $linked = 0,
        public int $missingLocalPage = 0,
        public int $skipped = 0,
    ) {}

    public function toArray(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'linked' => $this->linked,
            'missingLocalPage' => $this->missingLocalPage,
            'skipped' => $this->skipped,
        ];
    }
}
