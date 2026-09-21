<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;
use App\Filament\Forms\Components\CuratorUrlPicker;
use App\Models\Block;
use App\Models\CuratorMedia;
use Filament\Forms;
use Illuminate\Support\Arr;

final class KidsOpticsFrameCatalogDefinition extends AbstractBlockDefinition
{
    private const DEFAULT_TITLE = 'Каталог оправ';

    private const AGE_FILTERS = [
        '1-3' => '1-3 года',
        '3-7' => '3-7 лет',
        '7-12' => '7-12 лет',
        '12-18' => '12-18 лет',
    ];

    private const DEFAULT_CARDS = [
        [
            'title' => 'Бренд и модель',
            'description' => 'Лёгкая, гибкая, прочная',
            'age_group' => '3-7',
            'gender' => 'girl',
            'color_1' => '#1F3462',
            'color_2' => '#EFAEB0',
            'color_3' => '#735938',
        ],
        [
            'title' => 'Бренд и модель',
            'description' => 'Лёгкая, гибкая, прочная',
            'age_group' => '7-12',
            'gender' => 'boy',
            'color_1' => '#39505B',
            'color_2' => '#789C82',
            'color_3' => '#C5B7A0',
        ],
        [
            'title' => 'Бренд и модель',
            'description' => 'Лёгкая, гибкая, прочная',
            'age_group' => '7-12',
            'gender' => 'unisex',
            'color_1' => '#B06630',
            'color_2' => '#D99B73',
            'color_3' => '#4B5B7A',
        ],
        [
            'title' => 'Бренд и модель',
            'description' => 'Лёгкая, гибкая, прочная',
            'age_group' => '7-12',
            'gender' => 'boy',
            'color_1' => '#39505B',
            'color_2' => '#789C82',
            'color_3' => '#C5B7A0',
        ],
        [
            'title' => 'Бренд и модель',
            'description' => 'Лёгкая, гибкая, прочная',
            'age_group' => '7-12',
            'gender' => 'unisex',
            'color_1' => '#B06630',
            'color_2' => '#D99B73',
            'color_3' => '#4B5B7A',
        ],
        [
            'title' => 'Бренд и модель',
            'description' => 'Лёгкая, гибкая, прочная',
            'age_group' => '3-7',
            'gender' => 'girl',
            'color_1' => '#1F3462',
            'color_2' => '#EFAEB0',
            'color_3' => '#735938',
        ],
    ];

    public function type(): BlockType
    {
        return BlockType::KIDS_OPTICS_FRAME_CATALOG;
    }

    public function label(): string
    {
        return 'Каталог оправ «Детская оптика»';
    }

    public function view(): string
    {
        return 'components.block.kids-optics-frame-catalog';
    }

    public function formSchema(): array
    {
        return [
            Forms\Components\Section::make('Карточки оправ')
                ->description('Пока карточки редактируются в блоке. После добавления сущности «Оправы» данные будут поступать из каталога.')
                ->schema([
                    Forms\Components\Repeater::make('payload.items')
                        ->label('Оправы')
                        ->schema([
                            Forms\Components\TextInput::make('title')
                                ->label('Бренд и модель')
                                ->maxLength(120)
                                ->required(),

                            Forms\Components\TextInput::make('description')
                                ->label('Описание')
                                ->maxLength(160)
                                ->required(),

                            Forms\Components\Select::make('age_group')
                                ->label('Возраст')
                                ->options(self::AGE_FILTERS)
                                ->required(),

                            Forms\Components\Select::make('gender')
                                ->label('Кому подходит')
                                ->options([
                                    'boy' => 'Для мальчика',
                                    'girl' => 'Для девочки',
                                    'unisex' => 'Любой пол',
                                ])
                                ->required(),

                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\ColorPicker::make('color_1')
                                        ->label('Цвет 1'),
                                    Forms\Components\ColorPicker::make('color_2')
                                        ->label('Цвет 2'),
                                    Forms\Components\ColorPicker::make('color_3')
                                        ->label('Цвет 3'),
                                ]),

                            CuratorUrlPicker::make('curator_media_id')
                                ->label('Фотография оправы')
                                ->buttonLabel('Выбрать фотографию')
                                ->directory('kids-optics/frames')
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                ->helperText('Загружайте фото от 800 × 626 px: сайт сформирует лёгкие WebP-версии для карточек.')
                                ->columnSpanFull(),
                        ])
                        ->default(self::DEFAULT_CARDS)
                        ->defaultItems(6)
                        ->minItems(1)
                        ->maxItems(24)
                        ->reorderable()
                        ->collapsible()
                        ->itemLabel(fn (array $state): string => trim((string) ($state['title'] ?? '')) ?: 'Оправа')
                        ->columnSpanFull()
                        ->required(),
                ]),

            Forms\Components\Section::make('Кнопка')
                ->schema([
                    Forms\Components\TextInput::make('payload.show_more_desktop_text')
                        ->label('Текст кнопки на десктопе')
                        ->default('Показать ещё')
                        ->maxLength(80)
                        ->required(),

                    Forms\Components\TextInput::make('payload.show_more_mobile_text')
                        ->label('Текст кнопки на мобильном')
                        ->default('Показать ещё')
                        ->maxLength(80)
                        ->required(),
                ])->columns(2),
        ];
    }

    public function viewData(Block $block): array
    {
        $payload = (array) ($block->payload ?? []);
        $items = Arr::wrap($payload['items'] ?? self::DEFAULT_CARDS);
        $mediaById = CuratorMedia::query()
            ->whereKey(collect($items)
                ->map(fn ($item) => (int) data_get($item, 'curator_media_id'))
                ->filter()
                ->unique()
                ->all())
            ->get()
            ->keyBy(fn (CuratorMedia $media) => $media->getKey());

        return [
            'catalogTitle' => trim((string) $block->title) ?: self::DEFAULT_TITLE,
            'ageFilters' => self::AGE_FILTERS,
            'frames' => collect($items)
                ->filter(fn ($item): bool => is_array($item))
                ->values()
                ->map(function (array $item, int $index) use ($mediaById): array {
                    $gender = in_array($item['gender'] ?? null, ['boy', 'girl', 'unisex'], true)
                        ? $item['gender']
                        : 'unisex';

                    return [
                        'title' => trim((string) ($item['title'] ?? '')) ?: 'Бренд и модель',
                        'description' => trim((string) ($item['description'] ?? '')) ?: 'Лёгкая, гибкая, прочная',
                        'ageGroup' => array_key_exists((string) ($item['age_group'] ?? ''), self::AGE_FILTERS)
                            ? $item['age_group']
                            : null,
                        'gender' => $gender,
                        'genderLabel' => match ($gender) {
                            'boy' => 'Для мальчика',
                            'girl' => 'Для девочки',
                            default => 'Любой пол',
                        },
                        'colors' => collect([$item['color_1'] ?? null, $item['color_2'] ?? null, $item['color_3'] ?? null])
                            ->filter(fn ($color): bool => is_string($color) && preg_match('/^#[0-9a-f]{6}$/i', $color) === 1)
                            ->values()
                            ->all(),
                        'image' => $this->imageSources(
                            $mediaById->get((int) ($item['curator_media_id'] ?? 0)),
                            ($index % 3) + 1,
                        ),
                    ];
                })
                ->all(),
            'showMoreDesktopText' => trim((string) ($payload['show_more_desktop_text'] ?? '')) ?: 'Показать ещё',
            'showMoreMobileText' => trim((string) ($payload['show_more_mobile_text'] ?? '')) ?: 'Показать ещё',
        ];
    }

    /** @return array{avif: ?string, webp: ?string, webpSrcset: ?string, src: string, srcset: ?string} */
    private function imageSources(?CuratorMedia $media, int $fallbackImage): array
    {
        if ($media !== null && $media->resizable) {
            $webp400 = $media->getSignedUrl(['w' => 400, 'h' => 313, 'fit' => 'crop', 'fm' => 'webp']);
            $webp800 = $media->getSignedUrl(['w' => 800, 'h' => 626, 'fit' => 'crop', 'fm' => 'webp']);
            $jpg400 = $media->getSignedUrl(['w' => 400, 'h' => 313, 'fit' => 'crop', 'fm' => 'jpg']);
            $jpg800 = $media->getSignedUrl(['w' => 800, 'h' => 626, 'fit' => 'crop', 'fm' => 'jpg']);

            return [
                'avif' => null,
                'webp' => null,
                'webpSrcset' => "{$webp400} 400w, {$webp800} 800w",
                'src' => $jpg800,
                'srcset' => "{$jpg400} 400w, {$jpg800} 800w",
            ];
        }

        $basePath = "images/kids-optics/frame-catalog/card-{$fallbackImage}";

        return [
            'avif' => asset("{$basePath}.avif"),
            'webp' => asset("{$basePath}.webp"),
            'webpSrcset' => null,
            'src' => asset("{$basePath}.jpg"),
            'srcset' => null,
        ];
    }
}
