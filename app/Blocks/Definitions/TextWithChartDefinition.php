<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;
use App\Filament\Forms\Components\SafeMediaLibraryFileUpload;
use Filament\Forms;

final class TextWithChartDefinition extends AbstractBlockDefinition
{
    public function type(): BlockType
    {
        return BlockType::TEXT_WITH_CHART;
    }

    public function label(): string
    {
        return 'Текст с графиком';
    }

    public function view(): string
    {
        return 'components.block.text-with-chart';
    }

    public function formSchema(): array
    {
        return [
            Forms\Components\TextInput::make('payload.var_1_title')
                ->label('Вариант 1 заголовок')
                ->required(),
            Forms\Components\Textarea::make('payload.var_1_text')
                ->label('Вариант 1 текст')
                ->required(),
            Forms\Components\TextInput::make('payload.var_2_title')
                ->label('Вариант 2 заголовок')
                ->required(),
            Forms\Components\Textarea::make('payload.var_2_text')
                ->label('Вариант 2 текст')
                ->required(),
            SafeMediaLibraryFileUpload::make('payload.bg_chart')
                ->safeImages()
                ->label('Изображение графика')
                ->collection('bg_chart')
                ->imageEditor()
                ->responsiveImages()
                ->openable(),
        ];
    }
}
