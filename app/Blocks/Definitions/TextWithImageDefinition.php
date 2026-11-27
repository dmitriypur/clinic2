<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;
use App\Filament\Forms\Components\SafeMediaLibraryFileUpload;
use Filament\Forms;

final class TextWithImageDefinition extends AbstractBlockDefinition
{
    public function type(): BlockType
    {
        return BlockType::TEXT_WITH_IMAGE;
    }

    public function label(): string
    {
        return 'Текст с изображением';
    }

    public function view(): string
    {
        return 'components.block.text-with-image-container';
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

            Forms\Components\TextInput::make('payload.link')
                ->columnSpanFull()
                ->label('Ссылка на страницу'),

            Forms\Components\Toggle::make('payload.reverse')
                ->label('Перевернуть')
                ->default(false),

            Forms\Components\Toggle::make('payload.add_fox')
                ->label('Добавить лисенка')
                ->default(true),
        ];
    }
}
