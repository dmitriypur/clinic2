<?php

namespace App\Enums;

enum BlockBackgroundType: int
{
    case SURFACE = 0;

    case SUBDUED = 1;
    case BEIGE = 2;

    public function getLabel(): string
    {
        return match ($this) {
            self::SURFACE => 'Белый',
            self::SUBDUED => 'Серый',
            self::BEIGE => 'Бежевый',
        };
    }

    public static function toArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($value) => [$value->value => $value->getLabel()])
            ->toArray();
    }
}
