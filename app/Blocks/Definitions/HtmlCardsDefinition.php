<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;
use App\Filament\Forms\Components\CuratorUrlPicker;
use App\Models\Block;
use App\Support\HtmlCardSanitizer;
use Filament\Forms;
use Illuminate\Support\Arr;

final class HtmlCardsDefinition extends AbstractBlockDefinition
{
    public function __construct(private readonly HtmlCardSanitizer $sanitizer) {}

    public function type(): BlockType
    {
        return BlockType::HTML_CARDS;
    }

    public function label(): string
    {
        return 'HTML-карточки';
    }

    public function view(): string
    {
        return 'components.block.html-cards';
    }

    public function formSchema(): array
    {
        return [
            Forms\Components\Select::make('payload.layout')
                ->label('Режим вывода')
                ->options([
                    'grid' => 'Сетка',
                    'list' => 'Список горизонтальных карточек',
                ])
                ->default('grid')
                ->required()
                ->live(),

            Forms\Components\Select::make('payload.columns')
                ->label('Количество колонок')
                ->options(array_combine(range(1, 6), range(1, 6)))
                ->default(3)
                ->required()
                ->visible(fn (Forms\Get $get): bool => $get('payload.layout') === 'grid'),

            Forms\Components\Select::make('payload.gap')
                ->label('Расстояние между карточками')
                ->options([
                    'gap-0' => 'Без отступа',
                    'gap-2' => '8 px',
                    'gap-4' => '16 px',
                    'gap-6' => '24 px',
                    'gap-8' => '32 px',
                    'gap-10' => '40 px',
                    'gap-12' => '48 px',
                ])
                ->default('gap-6')
                ->required(),

            Forms\Components\TextInput::make('payload.card_classes')
                ->label('Общие Tailwind-классы карточек')
                ->default('w-full rounded-20 bg-white p-5 md:p-8')
                ->helperText('Например: w-full min-h-24 rounded-20 bg-white px-6 py-4')
                ->columnSpanFull(),

            Forms\Components\Repeater::make('payload.items')
                ->label('Карточки')
                ->schema([
                    Forms\Components\Textarea::make('html')
                        ->label('HTML-код')
                        ->rows(16)
                        ->required()
                        ->extraInputAttributes([
                            'class' => 'font-mono text-sm leading-6',
                            'spellcheck' => 'false',
                        ])
                        ->extraAlpineAttributes([
                            'x-on:keydown.tab.prevent' => <<<'JS'
                                const start = $el.selectionStart;
                                const end = $el.selectionEnd;
                                $el.value = $el.value.substring(0, start) + '    ' + $el.value.substring(end);
                                $el.selectionStart = $el.selectionEnd = start + 4;
                                $el.dispatchEvent(new Event('input'));
                            JS,
                        ])
                        ->helperText('HTML без JavaScript. Tab вставляет отступ.')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('classes')
                        ->label('Дополнительные Tailwind-классы')
                        ->helperText('Добавляются к общим классам только для этой карточки.')
                        ->columnSpanFull(),

                    CuratorUrlPicker::make('curator_media_id')
                        ->label('Изображение из медиатеки')
                        ->buttonLabel('Выбрать изображение')
                        ->listDisplay()
                        ->directory('html-cards')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'])
                        ->helperText('После выбора скопируйте URL и вставьте его в src тега <img>.')
                        ->columnSpanFull(),
                ])
                ->mutateDehydratedStateUsing(fn (array $state): array => collect($state)
                    ->map(function (array $item): array {
                        $item['html'] = app(HtmlCardSanitizer::class)->sanitize((string) ($item['html'] ?? ''));
                        $item['classes'] = trim((string) ($item['classes'] ?? ''));

                        return $item;
                    })
                    ->values()
                    ->all())
                ->defaultItems(1)
                ->minItems(1)
                ->reorderable()
                ->collapsible()
                ->itemLabel(fn (array $state): string => trim(strip_tags((string) ($state['html'] ?? ''))) ?: 'Карточка')
                ->columnSpanFull()
                ->required(),
        ];
    }

    public function viewData(Block $block): array
    {
        $payload = (array) ($block->payload ?? []);
        $layout = ($payload['layout'] ?? 'grid') === 'list' ? 'list' : 'grid';
        $columns = max(1, min(6, (int) ($payload['columns'] ?? 3)));
        $gap = in_array($payload['gap'] ?? null, self::gapOptions(), true)
            ? $payload['gap']
            : 'gap-6';
        $baseClasses = trim((string) ($payload['card_classes'] ?? ''));

        $items = collect(Arr::wrap($payload['items'] ?? []))
            ->filter(fn ($item): bool => is_array($item) && filled($item['html'] ?? null))
            ->map(fn (array $item): array => [
                'html' => $this->sanitizer->sanitize((string) $item['html']),
                'classes' => trim($baseClasses.' '.trim((string) ($item['classes'] ?? ''))),
            ])
            ->values()
            ->all();

        return [
            'items' => $items,
            'layoutClasses' => $layout === 'list'
                ? "flex flex-col {$gap}"
                : "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-{$columns} {$gap}",
        ];
    }

    /** @return array<string> */
    private static function gapOptions(): array
    {
        return ['gap-0', 'gap-2', 'gap-4', 'gap-6', 'gap-8', 'gap-10', 'gap-12'];
    }
}
