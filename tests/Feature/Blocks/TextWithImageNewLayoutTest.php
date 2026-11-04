<?php

namespace Tests\Feature\Blocks;

use App\Blocks\BlockRegistry;
use App\Blocks\Definitions\TextWithImageNewDefinition;
use App\Enums\BlockType;
use App\Models\Block;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Blade;
use Mockery;
use Tests\TestCase;

class TextWithImageNewLayoutTest extends TestCase
{
    public function test_block_is_registered_with_its_existing_view(): void
    {
        $definition = app(BlockRegistry::class)->find(BlockType::TEXT_WITH_IMAGE_NEW);
        $this->assertInstanceOf(TextWithImageNewDefinition::class, $definition);
        $this->assertSame('components.new-block.text-with-image-new', $definition->view());
    }

    public function test_registered_block_renders_through_the_page_component(): void
    {
        $block = Mockery::mock(Block::class)->makePartial();
        $block->forceFill(['type' => BlockType::TEXT_WITH_IMAGE_NEW, 'title' => 'Услуга', 'body_html' => '<p>Текст</p>', 'settings' => [], 'payload' => ['layout_variant' => 'service']]);
        $block->shouldReceive('getResponsiveImage')->andReturn($this->fakeImage());
        $html = Blade::render('<x-block :block="$block" breadcrumbs-title="" page-title="" page-description="" />', ['block' => $block]);
        $this->assertStringContainsString('service-text-image', $html);
        $this->assertStringContainsString('<p>Текст</p>', $html);
    }

    private function renderBlock(array $payload, string $html = '<h3>Заголовок</h3><p>Текст</p>'): string
    {
        $block = Mockery::mock(Block::class)->makePartial();
        $block->forceFill(['title' => 'Услуга', 'body_html' => $html, 'settings' => [], 'payload' => $payload]);
        $block->shouldReceive('getResponsiveImage')->andReturn($this->fakeImage());

        return view('components.new-block.text-with-image-new', ['block' => $block])->render();
    }

    public function test_existing_blocks_keep_their_original_layout(): void
    {
        $html = $this->renderBlock([]);
        $this->assertStringContainsString('bg-white', $html);
        $this->assertStringContainsString('md:p-10', $html);
        $this->assertStringNotContainsString('service-text-image', $html);
    }

    public function test_service_layout_keeps_text_before_image_and_reverses_only_on_desktop(): void
    {
        foreach (['left', 'right'] as $position) {
            $html = $this->renderBlock(['layout_variant' => 'service', 'image_position' => $position]);
            $this->assertStringContainsString('service-text-image', $html);
            $this->assertLessThan(strpos($html, 'src="/example.jpg"'), strpos($html, '<h3>Заголовок</h3>'));
            $this->assertSame($position === 'left', str_contains($html, 'md:order-first'));
            $this->assertStringContainsString('md:grid-cols-2', $html);
            $this->assertStringNotContainsString('bg-white', $html);
        }
    }

    public function test_service_layout_sanitizes_content(): void
    {
        $html = $this->renderBlock(['layout_variant' => 'service'], '<p>Текст</p><script>alert(1)</script>');
        $this->assertStringContainsString('<p>Текст</p>', $html);
        $this->assertStringNotContainsString('<script', $html);
    }

    private function fakeImage(): Htmlable
    {
        return new class implements Htmlable {
            public function lazy(): self { return $this; }
            public function toHtml(): string { return '<img src="/example.jpg" alt="Услуга">'; }
        };
    }
}
