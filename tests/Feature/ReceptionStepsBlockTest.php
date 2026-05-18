<?php

namespace Tests\Feature;

use App\Enums\BlockType;
use App\Models\Block;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ReceptionStepsBlockTest extends TestCase
{
    public function test_it_renders_reception_steps_block_items(): void
    {
        $block = new Block([
            'type' => BlockType::RECEPTION_STEPS,
            'title' => 'Как проходит прием у детского офтальмолога?',
            'payload' => [
                'items' => [
                    [
                        'title' => 'Знакомство и сбор анамнеза.',
                        'body_html' => '<p>Первый этап.</p>',
                    ],
                    [
                        'title' => 'Комплексная диагностика зрения.',
                        'body_html' => '<ul><li>Проверка остроты зрения.</li></ul>',
                    ],
                ],
            ],
            'settings' => [],
        ]);

        $html = Blade::render(
            '<x-block :block="$block" breadcrumbs-title="" page-title="" page-description="" />',
            ['block' => $block],
        );

        $this->assertStringContainsString('reception-steps-grid', $html);
        $this->assertStringContainsString('Этап №1', $html);
        $this->assertStringContainsString('Этап №2', $html);
        $this->assertStringContainsString('Проверка остроты зрения.', $html);
    }
}
