<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;
use Dotswan\FilamentCodeEditor\Fields\CodeEditor;
use Filament\Forms;

final class UniversalTextBlockDefinition extends AbstractBlockDefinition
{
    public function type(): BlockType
    {
        return BlockType::UNIVERSAL_TEXT_BLOCK;
    }

    public function label(): string
    {
        return 'Универсальный текстовый блок';
    }

    public function view(): string
    {
        return 'components.block.universal-block';
    }

    public function formSchema(): array
    {
        return [
            CodeEditor::make('payload.html')
                ->label('Текст HTML')
                ->id('universal-text-block-html')
                ->minHeight(400)
                ->lightModeTheme('basic-light')
                ->darkModeTheme('basic-dark')
                ->columnSpanFull(),

            Forms\Components\Repeater::make('payload.grid')
                ->label('Карточки документов')
                ->schema([
                    Forms\Components\TextInput::make('text')
                        ->label('Текст')
                        ->columnSpanFull(),

                    Forms\Components\FileUpload::make('document')
                        ->label('Документ')
                        ->directory('docs')
                        ->columnSpanFull(),
                ])
                ->reorderable()
                ->collapsible()
                ->itemLabel(fn (array $state): string => strip_tags((string) ($state['text'] ?? '')) ?: 'Документ')
                ->columnSpanFull(),
        ];
    }
}
