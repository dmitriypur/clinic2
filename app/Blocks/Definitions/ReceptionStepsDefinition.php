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
