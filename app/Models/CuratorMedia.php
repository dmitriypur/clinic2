<?php

namespace App\Models;

use App\Enums\BlockType;
use Awcodes\Curator\Models\Media;

class CuratorMedia extends Media
{
    protected $table = 'curator_media';

    public function getSignedUrl(array $params = [], bool $force = false): string
    {
        if ($this->directory === 'expert-opinions') {
            return $this->url;
        }

        return parent::getSignedUrl($params, $force);
    }

    public function isUsedByExpertOpinion(): bool
    {
        return $this->isUsedByBlocks();
    }

    public function isUsedByBlocks(): bool
    {
        $usedByExpertOpinion = Block::query()
            ->withoutGlobalScopes()
            ->where('type', BlockType::EXPERT_OPINION->value)
            ->where('payload->curator_image_id', $this->getKey())
            ->exists();

        if ($usedByExpertOpinion) {
            return true;
        }

        return Block::query()
            ->withoutGlobalScopes()
            ->where('type', BlockType::HTML_CARDS->value)
            ->get(['payload'])
            ->contains(fn (Block $block): bool => collect($block->payload['items'] ?? [])
                ->contains(fn ($item): bool => is_array($item)
                    && (int) ($item['curator_media_id'] ?? 0) === (int) $this->getKey()));
    }
}
