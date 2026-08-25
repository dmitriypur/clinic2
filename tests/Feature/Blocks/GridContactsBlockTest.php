<?php

namespace Tests\Feature\Blocks;

use App\Blocks\BlockRegistry;
use App\Enums\BlockType;
use App\Models\Block;
use Tests\TestCase;

class GridContactsBlockTest extends TestCase
{
    public function test_it_renders_existing_payload_through_the_block_registry(): void
    {
        $block = new Block([
            'type' => BlockType::GRID_CONTACTS,
            'title' => 'Контактная информация',
            'title_hidden' => false,
            'payload' => [
                'image' => 'corgi/contact-card.webp',
                'contacts' => [
                    [
                        'title' => 'Департамент здравоохранения Москвы',
                        'info' => '<ul><li>Адрес: Оружейный переулок, д. 43</li><li>Телефон: 8 (495) 777-77-77</li><li><a href="https://example.test">https://example.test</a></li></ul>',
                    ],
                ],
            ],
        ]);

        $definition = app(BlockRegistry::class)->find(BlockType::GRID_CONTACTS);

        $this->assertNotNull($definition);

        $html = view($definition->view(), [
            'block' => $block,
            ...$definition->viewData($block),
        ])->render();

        $this->assertStringContainsString('Контактная информация', $html);
        $this->assertStringContainsString('Департамент здравоохранения Москвы', $html);
        $this->assertStringContainsString('8 (495) 777-77-77', $html);
        $this->assertStringContainsString('Оружейный переулок, д. 43', $html);
        $this->assertStringContainsString('href="https://example.test"', $html);
        $this->assertStringContainsString('Сайт организации', $html);
        $this->assertStringContainsString('/storage/corgi/contact-card.webp', $html);
        $this->assertStringContainsString('/images/logo.svg', $html);
    }
}
