<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;
use App\Filament\Forms\Components\SafeMediaLibraryFileUpload;
use Filament\Forms;

final class ApparatusDiseasesDefinition extends AbstractBlockDefinition
{
    public function type(): BlockType
    {
        return BlockType::APPARATUS_DISEASES;
    }

    public function label(): string
    {
        return 'Блок "Заболевания для аппаратных процедур"';
    }

    public function view(): string
    {
        return 'components.block.apparatus-diseases';
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
            Forms\Components\Section::make([
                Forms\Components\Repeater::make('payload.items')
                    ->label('Карточки')
                    ->schema([
                        Forms\Components\Textarea::make('text')
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
