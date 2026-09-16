<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;
use App\Filament\Forms\Components\SafeMediaLibraryFileUpload;
use Filament\Forms;
use Illuminate\Support\Str;

final class DiagnosticMethodsDefinition extends AbstractBlockDefinition
{
    public function type(): BlockType
    {
        return BlockType::DIAGNOSTIC_METHODS;
    }

    public function label(): string
    {
        return 'Методы диагностики';
    }

    public function view(): string
    {
        return 'components.block.diagnostic-methods';
    }

    public function formSchema(): array
    {
        return [
            Forms\Components\RichEditor::make('body_html')
                ->label('Текст')
                ->columnSpanFull(),

            SafeMediaLibraryFileUpload::make('default')
                ->safeImages()
                ->label('Изображение')
                ->imageEditor()
                ->responsiveImages()
                ->openable(),

            Forms\Components\Textarea::make('payload.cards_intro')
                ->label('Подзаголовок перед карточками')
                ->columnSpanFull(),

            Forms\Components\Section::make([
                Forms\Components\Repeater::make('payload.items')
                    ->label('Методы диагностики')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Заголовок')
                            ->columnSpanFull()
                            ->required(),

                        Forms\Components\RichEditor::make('body_html')
                            ->label('Текст')
                            ->columnSpanFull()
                            ->required(),

                        Forms\Components\TextInput::make('link')
                            ->label('Ссылка')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('media_collection')
                            ->columnSpanFull()
                            ->hiddenLabel()
                            ->default(
                                fn (Forms\Get $get) => $get('media_collection') ?? Str::uuid()->toString()
                            )
                            ->reactive()
                            ->extraAttributes(['class' => 'hidden']),

                        SafeMediaLibraryFileUpload::make('image')
                            ->safeImages()
                            ->collection(fn (Forms\Get $get) => $get('media_collection'))
                            ->label('Мини-изображение')
                            ->imageEditor()
                            ->responsiveImages(),
                    ])
                    ->mutateDehydratedStateUsing(fn (array $state): array => collect($state)
                        ->map(function (array $item): array {
                            if (($item['text'] ?? null) === null) {
                                unset($item['text']);
                            }

                            return $item;
                        })
                        ->values()
                        ->all())
                    ->minItems(1)
                    ->required(),
            ]),
        ];
    }
}
