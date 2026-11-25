<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;
use Filament\Forms;

final class HowToOrderDefinition extends AbstractBlockDefinition
{
    public function type(): BlockType
    {
        return BlockType::HOW_TO_ORDER;
    }

    public function label(): string
    {
        return 'Блок "Как заказать"';
    }

    public function view(): string
    {
        return 'components.block.how-to-order';
    }

    public function formSchema(): array
    {
        return [
            Forms\Components\Repeater::make('payload.order')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Заголовок')
                        ->required(),
                    Forms\Components\RichEditor::make('text')
                        ->label('Текст')
                        ->required(),
                ])
                ->label('Карточка')
                ->columnSpanFull(),
        ];
    }
}
