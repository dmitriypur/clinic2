<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;
use App\Filament\Forms\Components\CuratorUrlPicker;
use App\Models\Block;
use App\Models\CuratorMedia;
use Dotswan\FilamentCodeEditor\Fields\CodeEditor;
use Filament\Forms;

final class DetailsDefinition extends AbstractBlockDefinition
{
    public function type(): BlockType
    {
        return BlockType::DETAILS;
    }

    public function label(): string
    {
        return 'Реквизиты';
    }

    public function view(): string
    {
        return 'components.block.details';
    }

    public function formSchema(): array
    {
        return [
            CodeEditor::make('payload.activity_html')
                ->label('HTML медицинской деятельности')
                ->id('details-activity-html')
                ->minHeight(400)
                ->lightModeTheme('basic-light')
                ->darkModeTheme('basic-dark')
                ->columnSpanFull(),

            Forms\Components\TextInput::make('payload.number_license')
                        ->label('Номер лицензии')
                        ->columnSpanFull(),

            CuratorUrlPicker::make('payload.activity_media_id')
                ->label('Изображение или PDF из медиатеки')
                ->buttonLabel('Выбрать файл')
                ->listDisplay()
                ->acceptedFileTypes([
                    'image/jpeg',
                    'image/png',
                    'image/webp',
                    'application/pdf',
                ])
                ->helperText('Скопируйте URL и вставьте его в HTML: <img src="..."> или <a href="...">.')
                ->columnSpanFull(),
        ];
    }

    public function viewData(Block $block): array
    {
        $mediaId = data_get($block->payload, 'activity_media_id');

        return [
            'activityMediaUrl' => filled($mediaId)
                ? CuratorMedia::query()->find($mediaId)?->url
                : null,
        ];
    }
}
