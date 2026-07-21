<?php

namespace App\Search;

class SiteSearchResult
{
    public function __construct(
        public readonly string $key,
        public readonly int $id,
        public readonly string $type,
        public readonly string $typeLabel,
        public readonly string $title,
        public readonly string $url,
        public readonly ?string $snippet,
        private readonly int $score,
    ) {
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function score(): int
    {
        return $this->score;
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => $this->typeLabel,
            'title' => $this->title,
            'url' => $this->url,
            'snippet' => $this->snippet,
        ];
    }
}
