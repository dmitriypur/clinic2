<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;
use Filament\Forms;

final class ApparatusTasksDefinition extends AbstractBlockDefinition
{
    public function type(): BlockType
    {
        return BlockType::APPARATUS_TASKS;
    }

    public function label(): string
    {
        return 'Блок "Ключевые задачи терапии"';
    }

    public function view(): string
    {
        return 'components.block.apparatus-tasks';
    }

    public function formSchema(): array
    {
        return [
            Forms\Components\Section::make([
                Forms\Components\Repeater::make('payload.tasks')
                    ->label('Пункты')
                    ->schema([
                        Forms\Components\TextInput::make('text')
                            ->label('Текст')
                            ->columnSpanFull()
                            ->required(),
                    ])
                    ->minItems(1)
                    ->required(),
                Forms\Components\Textarea::make('payload.note_text')
                    ->label('Текст нижнего блока')
                    ->columnSpanFull()
                    ->required(),
            ]),
        ];
    }
}
