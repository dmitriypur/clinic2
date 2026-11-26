<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;
use Filament\Forms;

final class GuaranteeDefinition extends AbstractBlockDefinition
{
    public function type(): BlockType
    {
        return BlockType::GUARANTEE;
    }

    public function label(): string
    {
        return 'Гарантийная программа';
    }

    public function view(): string
    {
        return 'components.block.guarantee';
    }

    public function formSchema(): array
    {
        return [
            Forms\Components\RichEditor::make('payload.enter_text')
                ->columnSpanFull()
                ->label('Начальный текст'),
            Forms\Components\Repeater::make('payload.guarantee')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Заголовок')
                        ->required(),
                    Forms\Components\Textarea::make('text')
                        ->label('Текст')
                        ->required(),
                ])
                ->label('Гарантии')
                ->required(),
        ];
    }
}
