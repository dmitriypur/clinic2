<?php

namespace App\Enums;

enum PageType: int
{
    case Default = 0;
    case Doctors = 1;

    case Services = 2;
    case Reviews = 3;

    case Posts = 4;

    case Blog = 5;

    public function getLabel(): string
    {
        return match ($this) {
            self::Default => 'Базовая страница с контентом',
            self::Doctors => 'Специалисты',
            self::Services => 'Услуги',
            self::Reviews => 'Отзывы',
            self::Posts => 'Посты',
            self::Blog => 'Блог',
        };
    }

    public static function toArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($value) => [$value->value => $value->getLabel()])
            ->sort()
            ->toArray();
    }

}
