<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;
use App\Filament\Forms\Components\SafeFileUpload;
use Filament\Forms;

final class TextBlocksDefinition extends AbstractBlockDefinition
{
    public function type(): BlockType
    {
        return BlockType::TEXT_BLOCKS;
    }

    public function label(): string
    {
        return 'Текстовые блоки';
    }

    public function view(): string
    {
        return 'components.block.text-blocks';
    }

    public function formSchema(): array
    {
        return [
            Forms\Components\Section::make([
                Forms\Components\Toggle::make('payload.is_opening')
                    ->label('Открывающийся')
                    ->default(true),
                Forms\Components\Toggle::make('payload.is_rounded')
                    ->label('Скруглить углы')
                    ->default(true),
            ])
                ->columns(4),

            Forms\Components\Repeater::make('payload.text_content')
                ->label('Текстовые блоки')
                ->schema([
                    Forms\Components\Section::make([
                        Forms\Components\Toggle::make('is_grid')
                            ->label('Сетка')
                            ->reactive()
                            ->default(false),
                    ])->columns(4),
                    Forms\Components\TextInput::make('title')
                        ->label('Заголовок'),
                    Forms\Components\TextInput::make('subtitle')
                        ->label('Подаголовок'),
                    Forms\Components\Section::make([
                        Forms\Components\RichEditor::make('body_html')
                            ->label('Текст'),
                        SafeFileUpload::make('picture')
                            ->safeImages()
                            ->label('Изображение'),
                    ]),

                    Forms\Components\Repeater::make('grid_blocks')
                        ->label('Сетка блоков')
                        ->reactive()
                        ->schema([
                            Forms\Components\RichEditor::make('body_html')
                                ->label('Текст'),
                            Forms\Components\Select::make('col_count')
                                ->label('Занимает колонок')
                                ->options([
                                    '1' => '1',
                                    '2' => '2',
                                    '3' => '3',
                                    '4' => '4',
                                ]),
                        ])
                        ->defaultItems(0)
                        ->hidden(
                            fn (Forms\Get $get) => $get('is_grid') === false
                        ),

                    Forms\Components\TextInput::make('cols_count')
                        ->label('Кол-во колонок')
                        ->hidden(
                            fn (Forms\Get $get) => $get('is_grid') === false
                        ),

                    Forms\Components\Select::make('classes')
                        ->label('Классы')
                        ->multiple()
                        ->options([
                            'gray md:rounded-xl bg-surface-subdued p-4 md:py-10 md:px-10 -mx-5 md:mx-0' => 'Серый фон',
                            'with-image-block' => 'Блок с изображением',
                            'text-center' => 'Текст по центру',
                        ]),
                ]),
        ];
    }
}
