<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;
use App\Models\Block;
use Filament\Forms;

final class DiagnosticAssessmentDefinition extends AbstractBlockDefinition
{
    private const ITEMS = [
        'Скрытые проявления дисграфии, дислексии и дискалькулии и их тип',
        'Понимание прочитанного текста',
        'Зрительно-пространственную ориентировку',
        'Зрительное восприятие и узнавание (зрительный гнозис)',
        'Зрительную память',
        'Внимание, саморегуляцию и контроль',
        'Эмоциональные реакции и особенности поведения',
    ];

    private const DESCRIPTION = 'Специалист анализирует не только результаты тестов, но и систематические ошибки ребёнка в тетрадях, его поведение и учебную историю. По итогам родители получают заключение с понятным объяснением, откуда берутся трудности, и план работы.';

    public function type(): BlockType
    {
        return BlockType::DIAGNOSTIC_ASSESSMENT;
    }

    public function label(): string
    {
        return $this->type()->legacyLabel();
    }

    public function view(): string
    {
        return 'components.block.diagnostic-assessment';
    }

    public function formSchema(): array
    {
        return [
            Forms\Components\Repeater::make('payload.assessment_items')
                ->label('Что оцениваем')
                ->simple(Forms\Components\TextInput::make('text')->required()->maxLength(255))
                ->default(self::ITEMS)
                ->addActionLabel('Добавить пункт')
                ->helperText('Если список пуст, отображаются пункты из макета.')
                ->columnSpanFull(),

            Forms\Components\Textarea::make('payload.assessment_description')
                ->label('Текст карточки')
                ->default(self::DESCRIPTION)
                ->maxLength(1000)
                ->helperText('Если поле пустое, отображается текст из макета.')
                ->columnSpanFull(),
        ];
    }

    public function viewData(Block $block): array
    {
        return [
            'assessmentTitle' => trim((string) $block->title) ?: $this->label(),
            'assessmentItems' => ($block->payload['assessment_items'] ?? []) ?: self::ITEMS,
            'assessmentDescription' => trim((string) ($block->payload['assessment_description'] ?? '')) ?: self::DESCRIPTION,
        ];
    }
}
