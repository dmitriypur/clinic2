<?php

namespace Tests\Feature;

use App\Enums\BlockType;
use App\Models\Block;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class TreatmentMethodsBlockTest extends TestCase
{
    public function test_it_renders_treatment_methods_block_content(): void
    {
        $block = new Block([
            'type' => BlockType::TREATMENT_METHODS,
            'title' => 'Как проводится лечение нарушений зрения у детей в клинике «Ангелы зрения»?',
            'body_html' => '<p>В нашей клинике мы предлагаем комплексные услуги по лечению и коррекции зрения.</p><p>Индивидуальный план лечения составляется с учетом возраста, образа жизни и особенностей заболевания каждого ребенка.</p>',
            'payload' => [
                'items' => [
                    [
                        'title' => 'Очки и линзы',
                        'body_html' => '<p>Данные способы коррекции зрения необходимы для создания четкого изображения на сетчатке.</p>',
                        'link' => '/treatment/ochki-i-linzy',
                    ],
                    [
                        'title' => 'Аппаратное лечение',
                        'body_html' => '<p>Широкий спектр услуг с использованием современного оборудования.</p>',
                        'link' => 'https://example.com/external-treatment',
                    ],
                ],
            ],
            'settings' => [],
        ]);

        $html = Blade::render(
            '<x-block :block="$block" breadcrumbs-title="" page-title="" page-description="" />',
            ['block' => $block],
        );

        $this->assertStringContainsString('treatment-methods-block', $html);
        $this->assertStringContainsString('Как проводится лечение нарушений зрения у детей', $html);
        $this->assertStringContainsString('Очки и линзы', $html);
        $this->assertStringContainsString('Аппаратное лечение', $html);
        $this->assertStringContainsString('hover:border-orange-300', $html);
        $this->assertStringContainsString('href="' . city_url('/treatment/ochki-i-linzy') . '"', $html);
        $this->assertStringContainsString('href="https://example.com/external-treatment"', $html);
    }
}
