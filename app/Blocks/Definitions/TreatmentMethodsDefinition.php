<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Support\Str;

final class TreatmentMethodsDefinition extends AbstractBlockDefinition
{
    public function type(): BlockType
    {
        return BlockType::TREATMENT_METHODS;
    }

    public function label(): string
    {
        return 'Методы лечения';
    }

    public function view(): string
    {
        return 'components.block.treatment-methods';
    }

    public function formSchema(): array
    {
        return [
            Forms\Components\RichEditor::make('body_html')
                ->label('Текст')
                ->columnSpanFull(),

            Forms\Components\Textarea::make('payload.cards_intro')
                ->label('Подзаголовок перед карточками')
                ->columnSpanFull(),

            Forms\Components\Section::make([
                Forms\Components\Repeater::make('payload.items')
                    ->label('Методы лечения')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Заголовок')
                            ->columnSpanFull()
                            ->required(),

                        Forms\Components\RichEditor::make('body_html')
                            ->label('Текст')
                            ->columnSpanFull()
                            ->required(),

                        Forms\Components\TextInput::make('media_collection')
                            ->columnSpanFull()
                            ->hiddenLabel()
                            ->default(
                                fn (Forms\Get $get) => $get('media_collection') ?? Str::uuid()->toString()
                            )
                            ->reactive()
                            ->extraAttributes(['class' => 'hidden'])
                            ->required(),

                        SpatieMediaLibraryFileUpload::make('image')
                            ->collection(fn (Forms\Get $get) => $get('media_collection'))
                            ->label('Мини-изображение')
                            ->imageEditor()
                            ->responsiveImages()
                            ->required(),
                    ])
                    ->minItems(1)
                    ->required(),
            ]),
        ];
    }
}
