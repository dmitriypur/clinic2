<?php

namespace Tests\Feature;

use App\Enums\BlockType;
use App\Models\Block;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class DiagnosticMethodsBlockTest extends TestCase
{
    public function test_it_renders_diagnostic_methods_block_content(): void
    {
        $block = new Block([
            'type' => BlockType::DIAGNOSTIC_METHODS,
            'title' => 'Какие методы диагностики зрения у детей мы используем?',
            'body_html' => '<p>Для максимально точной диагностики и составления эффективного плана лечения наши специалисты используют передовое высокоточное оборудование.</p>',
            'payload' => [
                'cards_intro' => 'Каждый дополнительный метод позволяет глубже изучить состояние зрительной системы:',
                'items' => [
                    [
                        'title' => 'Кератотопография',
                        'body_html' => '<p>Исследование роговицы глаза, которое строит ее «карту».</p>',
                        'link' => '/diagnostics/keratotopografiya',
                    ],
                    [
                        'title' => 'Тонометрия',
                        'body_html' => '<p>Измерение внутриглазного давления.</p>',
                    ],
                ],
            ],
            'settings' => [],
        ]);

        $html = Blade::render(
            '<x-block :block="$block" breadcrumbs-title="" page-title="" page-description="" />',
            ['block' => $block],
        );

        $this->assertStringContainsString('diagnostic-methods-block', $html);
        $this->assertStringContainsString('Какие методы диагностики зрения у детей мы используем?', $html);
        $this->assertStringContainsString('Каждый дополнительный метод позволяет глубже изучить состояние зрительной системы:', $html);
        $this->assertStringContainsString('Кератотопография', $html);
        $this->assertStringContainsString('Тонометрия', $html);
        $this->assertStringContainsString('href="' . city_url('/diagnostics/keratotopografiya') . '"', $html);
    }
}
