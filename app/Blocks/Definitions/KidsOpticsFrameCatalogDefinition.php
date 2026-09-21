<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;
use App\Models\Block;
use App\Services\FrameCatalogService;
use Filament\Forms;

final class KidsOpticsFrameCatalogDefinition extends AbstractBlockDefinition
{
    private const DEFAULT_TITLE = 'Каталог оправ';

    public function __construct(private readonly FrameCatalogService $catalog)
    {
    }

    public function type(): BlockType
    {
        return BlockType::KIDS_OPTICS_FRAME_CATALOG;
    }

    public function label(): string
    {
        return 'Каталог оправ «Детская оптика»';
    }

    public function view(): string
    {
        return 'components.block.kids-optics-frame-catalog';
    }

    public function formSchema(): array
    {
        return [
            Forms\Components\Section::make('Кнопка')
                ->description('Карточки редактируются в разделе «Каталог оправ».')
                ->schema([
                    Forms\Components\TextInput::make('payload.show_more_desktop_text')
                        ->label('Текст кнопки на десктопе')
                        ->default('Показать ещё')
                        ->maxLength(80)
                        ->required(),

                    Forms\Components\TextInput::make('payload.show_more_mobile_text')
                        ->label('Текст кнопки на мобильном')
                        ->default('Показать ещё')
                        ->maxLength(80)
                        ->required(),
                ])->columns(2),
        ];
    }

    public function viewData(Block $block): array
    {
        $payload = (array) ($block->payload ?? []);
        $page = $this->catalog->page();

        return [
            'catalogTitle' => trim((string) $block->title) ?: self::DEFAULT_TITLE,
            'ageFilters' => $this->catalog->ageFilters(),
            'frames' => $page['frames'],
            'totalFrames' => $page['total'],
            'catalogEndpoint' => route('api.frame-catalog.index'),
            'showMoreDesktopText' => trim((string) ($payload['show_more_desktop_text'] ?? '')) ?: 'Показать ещё',
            'showMoreMobileText' => trim((string) ($payload['show_more_mobile_text'] ?? '')) ?: 'Показать ещё',
        ];
    }
}
