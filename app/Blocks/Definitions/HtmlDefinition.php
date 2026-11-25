<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;
use Filament\Forms;

final class HtmlDefinition extends AbstractBlockDefinition
{
    public function type(): BlockType
    {
        return BlockType::HTML;
    }

    public function label(): string
    {
        return 'Текст';
    }

    public function view(): string
    {
        return 'components.block.html';
    }

    public function formSchema(): array
    {
        return [
            Forms\Components\RichEditor::make('body_html')
                ->label('Текст')
                ->columnSpan('full'),
        ];
    }
}
