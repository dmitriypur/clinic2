<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;
use App\Models\Block;
use DOMDocument;
use DOMElement;
use Filament\Forms;
use Illuminate\Support\Arr;

final class GridContactsDefinition extends AbstractBlockDefinition
{
    public function type(): BlockType
    {
        return BlockType::GRID_CONTACTS;
    }

    public function label(): string
    {
        return 'Сетка контактов';
    }

    public function view(): string
    {
        return 'components.block.contacts-info';
    }

    public function formSchema(): array
    {
        return [
            Forms\Components\Section::make([
                Forms\Components\FileUpload::make('payload.image')
                    ->label('Изображение')
                    ->directory('corgi'),

                Forms\Components\Repeater::make('payload.contacts')
                    ->label('Сетка контактов')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Название организации'),
                        Forms\Components\RichEditor::make('info')
                            ->label('Информация'),
                    ]),
            ]),
        ];
    }

    public function viewData(Block $block): array
    {
        $payload = (array) ($block->payload ?? []);

        return [
            'contacts' => collect(Arr::wrap($payload['contacts'] ?? []))
                ->filter(fn ($contact): bool => is_array($contact))
                ->map(fn (array $contact): array => [
                    'title' => trim((string) ($contact['title'] ?? '')),
                    'details' => $this->detailsFromHtml((string) ($contact['info'] ?? '')),
                    'rawInfo' => (string) ($contact['info'] ?? ''),
                ])
                ->values()
                ->all(),
            'imageUrl' => filled($payload['image'] ?? null)
                ? '/storage/'.ltrim((string) $payload['image'], '/')
                : null,
        ];
    }

    private function detailsFromHtml(string $html): array
    {
        if (blank($html)) {
            return [];
        }

        $document = new DOMDocument;
        $previousErrors = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);

        return collect($document->getElementsByTagName('li'))
            ->map(function (DOMElement $item): ?array {
                $link = $item->getElementsByTagName('a')->item(0);

                if ($link instanceof DOMElement && filled($link->getAttribute('href'))) {
                    return [
                        'label' => 'Сайт',
                        'value' => 'Сайт организации',
                        'url' => $link->getAttribute('href'),
                        'heading' => false,
                    ];
                }

                $text = trim((string) preg_replace('/\s+/u', ' ', $item->textContent));

                if ($text === '') {
                    return null;
                }

                if (preg_match('/^(Телефон)\s+(\+.+)$/u', $text, $matches) === 1) {
                    return $this->detail($matches[1], $matches[2]);
                }

                if (preg_match('/^([^:]{1,40}):\s*(.+)$/u', $text, $matches) === 1) {
                    return $this->detail($matches[1], $matches[2]);
                }

                return [
                    'label' => $text,
                    'value' => null,
                    'url' => null,
                    'heading' => true,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function detail(string $label, string $value): array
    {
        $label = match (trim($label)) {
            'Адрес электронной почты', 'E-mail' => 'Электронная почта',
            default => trim($label),
        };

        return [
            'label' => $label,
            'value' => trim($value),
            'url' => null,
            'heading' => false,
        ];
    }
}
