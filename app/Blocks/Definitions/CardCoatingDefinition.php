<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;
use Filament\Forms;

final class CardCoatingDefinition extends AbstractBlockDefinition
{
    public function type(): BlockType
    {
        return BlockType::CARD_COATING;
    }

    public function label(): string
    {
        return 'Блок "Покрытий линз"';
    }

    public function view(): string
    {
        return 'components.block.card-coating';
    }

    public function formSchema(): array
    {
        return [
            Forms\Components\Section::make([
                Forms\Components\Repeater::make('payload.coating')
                    ->label('Покрытие')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Заголовок')
                            ->columnSpanFull()
                            ->required(),
                        Forms\Components\RichEditor::make('list')
                            ->label('Выгоды')
                            ->columnSpanFull()
                            ->required(),
                        Forms\Components\Toggle::make('colors')
                            ->label('Показать цвета')
                            ->default(false)
                            ->reactive()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('btn_text')
                            ->label('Текст кнопки')
                            ->columnSpanFull()
                            ->required(),
                    ]),
            ]),
        ];
    }
}
