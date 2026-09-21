<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;
use App\Models\Block;
use App\Support\HtmlCardSanitizer;
use Dotswan\FilamentCodeEditor\Fields\CodeEditor;
use Filament\Forms;

final class KidsOpticsPackagesDefinition extends AbstractBlockDefinition
{
    private const DEFAULT_TITLE = 'Выбери свои очки';

    private const DEFAULT_BUTTON_TEXT = 'Записаться на подбор очков';

    private const DEFAULT_BASIC_CARD_HTML = <<<'HTML'
        <h3>Новые очки</h3>
        <p>Выгодная покупка. Идеально для первых очков или запасной пары на работу или в автомобиль.</p>
        <ul>
            <li>Новая коллекция оправ</li>
            <li>Утонченные линзы</li>
            <li>Покрытие, устойчивое к пыли, царапинам и бликам</li>
            <li>Индивидуальное изготовление очков</li>
        </ul>
        <p class="kids-optics-package-price">3 500 ₽</p>
        HTML;

    private const DEFAULT_PREMIUM_CARD_HTML = <<<'HTML'
        <p class="kids-optics-package-saving">Выгода 4 600 ₽</p>
        <h3>Любимые очки</h3>
        <p>Технологии, эстетика и статус. Очки, которые вы не захотите снимать.</p>
        <ul>
            <li>Расширенная коллекция оправ</li>
            <li>Утонченные линзы с антибликовым покрытием</li>
            <li>100% защита от UV-излучения (UV400)</li>
            <li>Стандартные корригирующие и астигматические очки</li>
            <li>Премиальный футляр и спрей для ухода</li>
        </ul>
        <p class="kids-optics-package-price">7 900 ₽</p>
        HTML;

    public function __construct(private readonly HtmlCardSanitizer $sanitizer) {}

    public function type(): BlockType
    {
        return BlockType::KIDS_OPTICS_PACKAGES;
    }

    public function label(): string
    {
        return 'Выбор очков «Детская оптика»';
    }

    public function view(): string
    {
        return 'components.block.kids-optics-packages';
    }

    public function formSchema(): array
    {
        return [
            Forms\Components\Section::make('Содержимое карточек')
                ->description('Если HTML-поле оставить пустым, на сайте будет показан встроенный текст из шаблона.')
                ->schema([
                    CodeEditor::make('payload.basic_card_html')
                        ->label('HTML карточки «Новые очки»')
                        ->id('kids-optics-basic-package-html')
                        ->minHeight(400)
                        ->lightModeTheme('basic-light')
                        ->darkModeTheme('basic-dark')
                        ->columnSpanFull(),

                    CodeEditor::make('payload.premium_card_html')
                        ->label('HTML карточки «Любимые очки»')
                        ->id('kids-optics-premium-package-html')
                        ->minHeight(440)
                        ->lightModeTheme('basic-light')
                        ->darkModeTheme('basic-dark')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('payload.button_text')
                        ->label('Текст кнопок')
                        ->default(self::DEFAULT_BUTTON_TEXT)
                        ->maxLength(80)
                        ->required()
                        ->columnSpanFull(),
                ]),
        ];
    }

    public function wrapperClassName(Block $block): ?string
    {
        return 'bg-surface-subdued py-8 md:py-12 xl:py-16';
    }

    public function viewData(Block $block): array
    {
        $payload = (array) ($block->payload ?? []);

        return [
            'packagesTitle' => trim((string) $block->title) ?: self::DEFAULT_TITLE,
            'basicCardHtml' => $this->cardHtml($payload['basic_card_html'] ?? null, self::DEFAULT_BASIC_CARD_HTML),
            'premiumCardHtml' => $this->cardHtml($payload['premium_card_html'] ?? null, self::DEFAULT_PREMIUM_CARD_HTML),
            'buttonText' => trim((string) ($payload['button_text'] ?? '')) ?: self::DEFAULT_BUTTON_TEXT,
            'basicCharacter' => $this->characterSources('basic'),
            'premiumCharacter' => $this->characterSources('premium'),
        ];
    }

    private function cardHtml(mixed $html, string $fallback): string
    {
        $html = trim((string) $html) ?: $fallback;

        return $this->sanitizer->sanitize($html);
    }

    /** @return array{avif: string, webp: string, fallback: string} */
    private function characterSources(string $variant): array
    {
        $basePath = "images/kids-optics/packages/{$variant}-character";

        return [
            'avif' => asset($basePath.'.avif'),
            'webp' => asset($basePath.'.webp'),
            'fallback' => asset($basePath.'.png'),
        ];
    }
}
