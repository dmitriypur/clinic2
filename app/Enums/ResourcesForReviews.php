<?php

namespace App\Enums;

enum ResourcesForReviews: int
{
    case Yandex = 1;
    case Sber = 3;
    case NaPopravku = 6;
    case Tgis = 4;
    case Prodoctorov = 2;
    case Zoon = 5;

    public function getLabel(): string
    {
        return match ($this) {
            self::Yandex => 'Яндекс отзывы',
            self::Sber => 'СберЗдоровье',
            self::Tgis => '2ГИС',
            self::Prodoctorov => 'Продокторов',
            self::Zoon => 'Zoon',
            self::NaPopravku => 'НаПоправку',
        };
    }

    public function getIcons(): string
    {
        return match ($this) {
            self::Yandex => 'images/resorces_for_reviews/yandex_maps_icon.png',
            self::Sber => 'images/resorces_for_reviews/sber.png',
            self::Tgis => 'images/resorces_for_reviews/2gis.png',
            self::Prodoctorov => 'images/resorces_for_reviews/prodoctorov.png',
            self::Zoon => 'images/resorces_for_reviews/zoon.png',
            self::NaPopravku => 'images/resorces_for_reviews/na-popravku.png',
        };
    }

    public static function toArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($value) => [$value->value => $value->getLabel()])
            ->sortKeys()
            ->toArray();
    }

    public static function icons(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($value) => [$value->value => $value->getIcons()])
            ->sort()
            ->toArray();
    }

}
