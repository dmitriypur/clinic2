<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;
use App\Filament\Forms\Components\SafeMediaLibraryFileUpload;
use Filament\Forms;

final class TextWithImageNewDefinition extends AbstractBlockDefinition
{
    public function type(): BlockType
    {
        return BlockType::TEXT_WITH_IMAGE_NEW;
    }

    public function label(): string
    {
        return 'Текст с изображением (новый дизайн)';
    }

    public function view(): string
    {
        return 'components.new-block.text-with-image-new';
    }

    public function formSchema(): array
    {
        return [
            Forms\Components\RichEditor::make('body_html')
                ->label('Текст')
                ->columnSpanFull(),

            Forms\Components\Select::make('payload.layout_variant')
                ->label('Оформление блока')
                ->options([
                    'card' => 'Карточка (существующий вид)',
                    'service' => 'Равные колонки без карточки',
                ])
                ->default('card')
                ->live(),

            Forms\Components\Select::make('payload.image_position')
                ->label('Позиция изображения')
                ->options(['right' => 'Справа', 'left' => 'Слева'])
                ->default('right')
                ->helperText('На телефоне изображение отображается после текста.')
                ->visible(fn (Forms\Get $get): bool => $get('payload.layout_variant') === 'service'),

            SafeMediaLibraryFileUpload::make('default')
                ->safeImages()
                ->label('Изображение')
                ->imageEditor()
                ->responsiveImages()
                ->openable(),
        ];
    }
}
