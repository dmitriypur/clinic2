<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;
use Filament\Forms;

final class HtmlCodeDefinition extends AbstractBlockDefinition
{
    public function type(): BlockType
    {
        return BlockType::HTML_CODE;
    }

    public function label(): string
    {
        return 'HTML-код';
    }

    public function view(): string
    {
        return 'components.block.html-code';
    }

    public function formSchema(): array
    {
        return [
            Forms\Components\Textarea::make('body_html')
                ->label('Текст')
                ->columnSpan('full'),
        ];
    }
}
