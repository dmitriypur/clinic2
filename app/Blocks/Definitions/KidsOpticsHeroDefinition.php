<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;
use App\Models\Block;
use Filament\Forms;

final class KidsOpticsHeroDefinition extends AbstractBlockDefinition
{
    private const DEFAULT_TITLE = 'Детская оптика';

    private const DEFAULT_DESCRIPTION = 'В наших салонах оптики представлены оправы для детей разных возрастов. Подберём идеальную оправу под ваши задачи и возраст ребёнка!';

    private const DEFAULT_BUTTON_TEXT = 'Записаться на подбор очков';

    public function type(): BlockType
    {
        return BlockType::KIDS_OPTICS_HERO;
    }

    public function label(): string
    {
        return 'Hero-баннер «Детская оптика»';
    }

    public function view(): string
    {
        return 'components.block.kids-optics-hero';
    }

    public function formSchema(): array
    {
        return [
            Forms\Components\Section::make('Содержимое hero-баннера')
                ->description('Заголовок баннера задаётся в основном поле «Заголовок» блока.')
                ->schema([
                    Forms\Components\Textarea::make('payload.description')
                        ->label('Описание')
                        ->default(self::DEFAULT_DESCRIPTION)
                        ->rows(3)
                        ->maxLength(220)
                        ->columnSpanFull()
                        ->required(),

                    Forms\Components\TextInput::make('payload.button_text')
                        ->label('Текст кнопки')
                        ->default(self::DEFAULT_BUTTON_TEXT)
                        ->maxLength(80)
                        ->columnSpanFull()
                        ->required(),
                ]),
        ];
    }

    public function wrapperClassName(Block $block): ?string
    {
        return 'bg-white';
    }

    public function viewData(Block $block): array
    {
        return [
            'heroId' => 'kids-optics-hero-'.($block->getKey() ?? 'preview'),
            'heroTitle' => trim((string) $block->title) ?: self::DEFAULT_TITLE,
            'heroDescription' => trim((string) data_get($block->payload, 'description')) ?: self::DEFAULT_DESCRIPTION,
            'heroButtonText' => trim((string) data_get($block->payload, 'button_text')) ?: self::DEFAULT_BUTTON_TEXT,
            'desktopImages' => $this->imageSources('desktop'),
            'mobileImages' => $this->imageSources('mobile'),
        ];
    }

    /** @return array{avif: string, webp: string, fallback: string} */
    private function imageSources(string $variant): array
    {
        $basePath = "images/kids-optics/hero/{$variant}";

        return [
            'avif' => asset($basePath.'.avif'),
            'webp' => asset($basePath.'.webp'),
            'fallback' => asset($basePath.'.jpg'),
        ];
    }
}
