<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;
use Filament\Forms;

final class ReceptionStepsDefinition extends AbstractBlockDefinition
{
    public function type(): BlockType
    {
        return BlockType::RECEPTION_STEPS;
    }

    public function label(): string
    {
        return 'Этапы приема';
    }

    public function view(): string
    {
        return 'components.block.reception-steps';
    }

    public function formSchema(): array
    {
        return [
            Forms\Components\Section::make([
                Forms\Components\Repeater::make('payload.items')
                    ->label('Этапы')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Заголовок')
                            ->columnSpanFull()
                            ->required(),

                        Forms\Components\RichEditor::make('body_html')
                            ->label('Текст')
                            ->columnSpanFull()
                            ->required(),
                    ])
                    ->minItems(1)
                    ->required(),
            ]),
        ];
    }
}
