<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;
use Filament\Forms;

final class TagsDefinition extends AbstractBlockDefinition
{
    public function type(): BlockType
    {
        return BlockType::TAGS;
    }

    public function label(): string
    {
        return 'Тэги';
    }

    public function view(): string
    {
        return 'components.block.tags';
    }

    public function formSchema(): array
    {
        return [
            Forms\Components\Repeater::make('payload.tags')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Название')
                        ->required(),
                    Forms\Components\TextInput::make('link')
                        ->label('Ссылка')
                        ->required(),
                ]),
        ];
    }
}
