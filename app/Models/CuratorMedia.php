<?php

namespace App\Models;

use App\Enums\BlockType;
use Awcodes\Curator\Models\Media;

class CuratorMedia extends Media
{
    protected $table = 'curator_media';

    public function isUsedByExpertOpinion(): bool
    {
        return Block::query()
            ->withoutGlobalScopes()
            ->where('type', BlockType::EXPERT_OPINION->value)
            ->where('payload->curator_image_id', $this->getKey())
            ->exists();
    }
}
