<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;
use App\Filament\Forms\Components\SafeFileUpload;
use Filament\Forms;

final class FaqDefinition extends AbstractBlockDefinition
{
    public function type(): BlockType
    {
        return BlockType::FAQ;
    }

    public function label(): string
    {
        return 'FAQ';
    }

    public function view(): string
    {
        return 'components.block.faq';
    }

    public function formSchema(): array
    {
        return [
            Forms\Components\Repeater::make('payload.faq')
                ->schema([
                    Forms\Components\TextInput::make('question')
                        ->label('Вопрос')
                        ->required(),

                    Forms\Components\RichEditor::make('answer_html')
                        ->label('Ответ')
                        ->required(),

                    SafeFileUpload::make('icon')
                        ->safeImages()
                        ->label('Иконка'),
                ])
                ->required()
                ->columnSpanFull(),
        ];
    }
}
