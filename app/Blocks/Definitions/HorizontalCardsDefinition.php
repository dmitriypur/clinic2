<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;
use App\Filament\Forms\Components\SafeMediaLibraryFileUpload;
use App\Models\Block;
use Filament\Forms;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\ResponsiveImages\ResponsiveImage;

final class HorizontalCardsDefinition extends AbstractBlockDefinition
{
    public function type(): BlockType
    {
        return BlockType::CARDS_ITEM_ROW;
    }

    public function label(): string
    {
        return 'Горизонтальные карточки';
    }

    public function view(): string
    {
        return 'components.block.cards-row';
    }

    public function viewData(Block $block): array
    {
        return [
            'items' => collect($block->payload['elements'] ?? [])
                ->map(function (array $item) use ($block): array {
                    $media = ! empty($item['media_collection'])
                        ? $block->getFirstMedia($item['media_collection'])
                        : null;
                    $conversion = $media?->hasGeneratedConversion('main') ? 'main' : '';

                    return [
                        ...$item,
                        'has_extra_info' => ! empty($item['body_html'])
                            || ! empty($item['has_price'])
                            || ! empty($item['has_an_appointment']),
                        'image' => $media ? [
                            'src' => $media->getUrl($conversion),
                            'srcset' => $media->responsiveImages($conversion)->files
                                ->map(fn (ResponsiveImage $image): string => $image->url().' '.$image->width().'w')
                                ->implode(', '),
                            'alt' => $block->getImageAltText($item['title']),
                            'title' => $block->getImageTitleText($item['title']),
                        ] : null,
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    public function formSchema(): array
    {
        return [
            Forms\Components\Select::make('payload.columns')
                ->label('Количество колонок')
                ->options([2 => '2', 3 => '3'])
                ->in([2, 3])
                ->default(2)
                ->afterStateHydrated(fn (Forms\Components\Select $component, $state) => $component->state($state ?? 2))
                ->helperText('3 колонки — компактные карточки. На телефоне всегда одна колонка.')
                ->required(),

            Forms\Components\Section::make([
                Forms\Components\Repeater::make('payload.elements')
                    ->label('Элементы')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Заголовок')
                            ->columnSpanFull()
                            ->required(),

                        Forms\Components\TextInput::make('subtitle')
                            ->label('Подзаголовок')
                            ->columnSpanFull(),

                        Forms\Components\RichEditor::make('body_html')
                            ->label('Текст')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('media_collection')
                            ->columnSpanFull()
                            ->hiddenLabel()
                            ->default(fn (Forms\Get $get) => $get('media_collection') ?? Str::uuid()->toString())
                            ->reactive()
                            ->required()
                            ->extraAttributes(['class' => 'hidden']),

                        SafeMediaLibraryFileUpload::make('image')
                            ->safeImages()
                            ->collection(fn (Forms\Get $get) => $get('media_collection'))
                            ->label('Изображение')
                            ->responsiveImages(),
                    ]),
            ]),
        ];
    }
}
