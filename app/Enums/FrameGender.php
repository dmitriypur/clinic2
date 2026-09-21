<?php

declare(strict_types=1);

namespace App\Enums;

enum FrameGender: string
{
    case BOY = 'boy';
    case GIRL = 'girl';

    public function cardLabel(): string
    {
        return match ($this) {
            self::BOY => 'Для мальчика',
            self::GIRL => 'Для девочки',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            self::BOY->value => 'Для мальчиков',
            self::GIRL->value => 'Для девочек',
        ];
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(
            static fn (self $gender): string => $gender->value,
            self::cases(),
        );
    }
}
