<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;
use App\Filament\Forms\Components\SafeMediaLibraryFileUpload;
use Filament\Forms;

abstract class ServiceBannerDefinition extends AbstractBlockDefinition
{
    final public function formSchema(): array
    {
        $type = $this->type();
        $pricingFields = in_array($type, [
            BlockType::BANNER_WITH_BUTTON,
            BlockType::BANNER_NIGHT_LENSES,
            BlockType::BANNER_APPOINTMENT,
        ], true) ? [
            Forms\Components\TextInput::make('payload.service_hero_subtitle')
                ->columnSpanFull()
                ->label('Подзаголовок'),
            Forms\Components\TextInput::make('payload.old_price')
                ->columnSpanFull()
                ->label('Старая цена'),
            Forms\Components\TextInput::make('payload.price')
                ->columnSpanFull()
                ->label('Цена'),
        ] : [];

        $buttonFields = $type === BlockType::BANNER_SELECTION_GLASSES ? [
            Forms\Components\TextInput::make('payload.btn_text')
                ->columnSpanFull()
                ->label('Текст кнопки'),
        ] : [];

        return [
            ...$buttonFields,
            Forms\Components\Section::make([
                Forms\Components\TextInput::make('payload.service_hero_title')
                    ->columnSpanFull()
                    ->label('Заголовок'),
                ...$pricingFields,
                Forms\Components\Textarea::make('payload.service_hero_text')
                    ->columnSpanFull()
                    ->label('Текст'),
                SafeMediaLibraryFileUpload::make('bg')
                    ->safeImages()
                    ->label('Фон (desktop)')
                    ->collection('bg')
                    ->imageEditor()
                    ->responsiveImages()
                    ->openable(),
                SafeMediaLibraryFileUpload::make('pic')
                    ->safeImages()
                    ->label('Изображение (mobile)')
                    ->collection('pic')
                    ->responsiveImages()
                    ->imageEditor()
                    ->openable(),
            ]),
        ];
    }
}
