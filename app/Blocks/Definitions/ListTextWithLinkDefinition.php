<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;
use App\Models\Block;
use Filament\Forms;
use Illuminate\Support\Arr;

final class ListTextWithLinkDefinition extends AbstractBlockDefinition
{
    public function type(): BlockType
    {
        return BlockType::LIST_TEXT_WITH_LINK;
    }

    public function label(): string
    {
        return 'Список текст со ссылкой';
    }

    public function view(): string
    {
        return 'components.block.list-text-with-link';
    }

    public function formSchema(): array
    {
        return [
            Forms\Components\Section::make([
                Forms\Components\Repeater::make('payload.grid')
                    ->label('Сетка карточек')
                    ->schema([
                        Forms\Components\TextInput::make('text')
                            ->label('Текст')
                            ->columnSpan('full'),

                        Forms\Components\FileUpload::make('document')
                            ->label('Документ')
                            ->reactive()
                            ->directory('docs')
                            ->hidden(fn (Forms\Get $get): bool => filled($get('link'))),

                        Forms\Components\TextInput::make('link')
                            ->label('Ссылка')
                            ->reactive()
                            ->columnSpan('full')
                            ->hidden(fn (Forms\Get $get): bool => filled($get('document'))),
                    ]),
            ]),
        ];
    }

    public function viewData(Block $block): array
    {
        $payload = (array) ($block->payload ?? []);

        return [
            'items' => collect(Arr::wrap($payload['grid'] ?? []))
                ->filter(fn ($item): bool => is_array($item))
                ->map(function (array $item): array {
                    $document = trim((string) ($item['document'] ?? ''));
                    $link = trim((string) ($item['link'] ?? ''));

                    return [
                        'text' => (string) ($item['text'] ?? ''),
                        'url' => match (true) {
                            $document !== '' => '/storage/'.ltrim($document, '/'),
                            $link !== '' => city_route('pages.show', ['handle' => $link]),
                            default => null,
                        },
                        'actionLabel' => $document !== '' ? 'Открыть документ' : 'Подробнее',
                        'newTab' => $document !== '',
                    ];
                })
                ->values()
                ->all(),
        ];
    }
}
