<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;
use Filament\Forms;

final class TextSubduedDefinition extends AbstractBlockDefinition
{
    public function type(): BlockType
    {
        return BlockType::TEXT_SUBDUED;
    }

    public function label(): string
    {
        return 'Второстепенный текст';
    }

    public function view(): string
    {
        return 'components.block.text-subdued';
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
