<?php

namespace App\Enums;

enum BlockType: int
{
    case TEXT_WITH_IMAGE = 0;

//    case INLINE_IMAGES = 1;
//
//    case GRID_IMAGES = 2;

    case CONTACTS = 4;

    case ELEMENTS_ITEM_COLUMN = 5;

    case ELEMENTS_ITEM_ROW = 6;

    case PROMOTIONS = 7;

    case TAGS = 8;

    case CAROUSEL = 9;

    case HTML = 10;

    case VIDEO = 11;

    case PRICE_LIST = 12;

    case CALL_TO_ACTION = 14;

    case LICENSES = 15;

    case FAQ = 16;

    case AUTHOR = 17;

    case TEXT_SUBDUED = 18;

    case MAIN_PAGE_STATIC_BLOCK = 19;

    case PHOTO = 20;

    case HTML_CODE = 21;

    case FULL_PRICE_LIST = 22;

    case UTP = 23;

    case WELCOME = 24;

    case GRID_CAROUSEL = 25;

    case TEXT_WITH_IMAGE_ALT = 26;

    case VIDEO_CAROUSEL = 27;

    case POINTS = 28;

    case BANNER_WITH_BUTTON = 29;

    case ADVANTAGES = 30;

    case VIDEO_NEW = 31;

    case TEXT_WITH_CHART = 32;

    case CARD_COATING = 33;

    case HOW_TO_ORDER = 34;

    case DOCTORS_ALT = 35;

    case TEXT_BLOCKS = 36;

    case REVIEWS_ALT = 37;

    case GUARANTEE = 38;

    case SERVICES_BLOCK = 39;

    case CARDS_ITEM_ROW = 40;

    case BANNER_NIGHT_LENSES = 41;

    case NIGHT_LENSES_PICTURES = 42;

    case NIGHT_LENSES_SELECTION = 43;
    case SEVERAL_COLS = 44;

    case BANNER_APPOINTMENT = 45;

    case CARDS_SLIDER = 46;

    case CARDS_FEATURE = 47;

    case SELECT_LENSES_SELECTION = 48;

    case PICTURE = 49;

    case POST_TEXT = 50;

    case BANNER_CORRECTION = 51;

    case BANNER_MYOPIA = 52;

    case CARDS_BORDER = 53;

    case LIST_WITH_IMAGE = 54;

    case BANNERS_GRID = 55;

    case ADVANTAGES_SLIDER = 56;

    case DETAILS = 57;
    case UNIVERSAL_TEXT_BLOCK = 58;
    case GRID_CONTACTS = 59;
    case LIST_TEXT_WITH_LINK = 60;

    public function getLabel(): string
    {
        return match ($this) {
            self::TEXT_WITH_IMAGE => 'Текст с изображением',
//            self::INLINE_IMAGES => 'Изображения с описанием (каждый элемент расположен вертикально)',
//            self::GRID_IMAGES => 'Изображения с описанием (каждый элемент расположен горизонтально)',
            self::ELEMENTS_ITEM_COLUMN => 'Элементы (каждый элемент расположен вертикально)',
            self::ELEMENTS_ITEM_ROW => 'Элементы (каждый элемент расположен горизонтально)',
            self::CONTACTS => 'Контакты',
            self::PROMOTIONS => 'Акции',
            self::TAGS => 'Тэги',
            self::CAROUSEL => 'Карусель изображений',
            self::HTML => 'Текст',
            self::VIDEO => 'Видео',
            self::VIDEO_NEW => 'Видео новый дизайн',
            self::PRICE_LIST => 'Прайс-лист на услугу',
            self::FULL_PRICE_LIST => 'Прайс-лист на все услуги',
            self::CALL_TO_ACTION => 'Форма заявки',
            self::LICENSES => 'Лицензии и сертификаты',
            self::FAQ => 'FAQ',
            self::AUTHOR => 'Автор статьи',
            self::TEXT_SUBDUED => 'Второстепенный текст',
            self::MAIN_PAGE_STATIC_BLOCK => 'Нередактируемый блок на главной странице',
            self::PHOTO => 'Фото',
            self::HTML_CODE => 'HTML-код',
            self::UTP => 'Блок УТП',
            self::WELCOME => 'Блок "Обращение главного врача"',
            self::GRID_CAROUSEL => 'Сетка (карусель на мобильных устройствах)',
            self::TEXT_WITH_IMAGE_ALT => 'Текст с изображением (альтернативный)',
            self::VIDEO_CAROUSEL => 'Видео карусель',
            self::POINTS => 'Пункты',
            self::BANNER_WITH_BUTTON => 'Блок "Баннер для страницы Stellest"',
            self::ADVANTAGES => 'Преимущества',
            self::TEXT_WITH_CHART => 'Текст с графиком',
            self::CARD_COATING => 'Блок "Покрытий линз"',
            self::HOW_TO_ORDER => 'Блок "Как заказать"',
            self::DOCTORS_ALT => 'Специалисты (альтернативный)',
            self::TEXT_BLOCKS => 'Текстовые блоки',
            self::REVIEWS_ALT => 'Отзывы (альтернативный)',
            self::GUARANTEE => 'Гарантийная программа',
            self::SERVICES_BLOCK => 'Блок "Услуги"',
            self::CARDS_ITEM_ROW => 'Горизонтальные карточки',
            self::BANNER_NIGHT_LENSES => 'Блок "Баннер для страницы Ночные линзы"',
            self::NIGHT_LENSES_PICTURES => 'Ночные линзы (иллюстрации)',
            self::NIGHT_LENSES_SELECTION => 'Блок "Подбор ночных линз"',
            self::SELECT_LENSES_SELECTION => 'Блок "Подбор мягких линз"',
            self::SEVERAL_COLS => 'Текстовый блок в несколько колонок',
            self::BANNER_APPOINTMENT => 'Блок "Баннер для страницы Приём детского офтальмолога"',
            self::CARDS_SLIDER => 'Слайдер карточек',
            self::CARDS_FEATURE => 'Блок "Особенности линз MiSight"',
            self::PICTURE => 'Изображение',
            self::POST_TEXT => 'Текстовые блоки для статей',
            self::BANNER_CORRECTION => 'Блок "Баннер для страницы Коррекция астигматизма"',
            self::BANNER_MYOPIA => 'Блок "Баннер для страницы Лечение близорукости"',
            self::CARDS_BORDER => 'Карточки с бордером',
            self::LIST_WITH_IMAGE => 'Список с изображением',
            self::BANNERS_GRID => 'Сетка баннеров',
            self::ADVANTAGES_SLIDER => 'Слайдер Преимущества',
            self::DETAILS => 'Реквизиты',
            self::UNIVERSAL_TEXT_BLOCK => 'Универсальный текстовый блок',
            self::GRID_CONTACTS => 'Сетка контактов',
            self::LIST_TEXT_WITH_LINK => 'Список текст со ссылкой',
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
