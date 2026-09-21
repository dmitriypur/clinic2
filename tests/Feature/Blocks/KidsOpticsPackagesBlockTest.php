<?php

namespace Tests\Feature\Blocks;

use App\Blocks\BlockRegistry;
use App\Enums\BlockType;
use App\Models\Block;
use Filament\Forms\Components\Component;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class KidsOpticsPackagesBlockTest extends TestCase
{
    public function test_packages_block_type_is_available(): void
    {
        $type = BlockType::tryFrom(82);

        $this->assertNotNull($type);
        $this->assertSame('Выбор очков «Детская оптика»', $type?->getLabel());
    }

    public function test_packages_block_is_registered_with_its_view(): void
    {
        $definition = app(BlockRegistry::class)->find(BlockType::KIDS_OPTICS_PACKAGES);

        $this->assertNotNull($definition);
        $this->assertSame('components.block.kids-optics-packages', $definition?->view());
        $this->assertTrue(View::exists('components.block.kids-optics-packages'));
    }

    public function test_empty_admin_fields_render_the_built_in_card_content(): void
    {
        $definition = app(BlockRegistry::class)->find(BlockType::KIDS_OPTICS_PACKAGES);
        $block = new Block([
            'type' => BlockType::KIDS_OPTICS_PACKAGES,
            'title' => '',
            'payload' => [],
        ]);

        $viewData = $definition->viewData($block);
        $html = view($definition->view(), $viewData)->render();

        $this->assertArrayHasKey('packagesTitle', $viewData);
        $this->assertSame('Выбери свои очки', $viewData['packagesTitle']);
        $this->assertStringContainsString('<h3>Новые очки</h3>', $viewData['basicCardHtml']);
        $this->assertStringContainsString('3 500 ₽', $viewData['basicCardHtml']);
        $this->assertStringContainsString('<h3>Любимые очки</h3>', $viewData['premiumCardHtml']);
        $this->assertStringContainsString('Выгода 4 600 ₽', $viewData['premiumCardHtml']);
        $this->assertStringContainsString('7 900 ₽', $viewData['premiumCardHtml']);
        $this->assertSame(2, substr_count($html, 'data-kids-optics-package'));
        $this->assertSame(2, substr_count($html, "showCallbackModal(null, 'otpravka-formy')"));
        $this->assertStringContainsString("[&_li]:before:content-['']", $html);
        $this->assertStringContainsString('[&_li]:flex', $html);
        $this->assertStringContainsString('[&_li]:items-center', $html);
        $this->assertStringContainsString('[&_li]:before:top-1/2', $html);
        $this->assertStringContainsString('[&_li]:before:-translate-y-1/2', $html);
        $this->assertStringContainsString("[&_.kids-optics-package-saving]:before:content-['']", $html);
        $this->assertSame(4, substr_count($html, '--kids-optics-check-icon'));
        $this->assertStringContainsString('--kids-optics-discount-icon', $html);
    }

    public function test_admin_html_overrides_defaults_and_is_sanitized(): void
    {
        $definition = app(BlockRegistry::class)->find(BlockType::KIDS_OPTICS_PACKAGES);
        $block = new Block([
            'type' => BlockType::KIDS_OPTICS_PACKAGES,
            'title' => 'Другой заголовок',
            'payload' => [
                'basic_card_html' => '<h3>Своя базовая карточка</h3><script>alert(1)</script>',
                'premium_card_html' => '<h3>Своя премиальная карточка</h3><img src="x" onerror="alert(1)">',
                'button_text' => 'Записаться',
            ],
        ]);

        $viewData = $definition->viewData($block);

        $this->assertSame('Другой заголовок', $viewData['packagesTitle']);
        $this->assertSame('Записаться', $viewData['buttonText']);
        $this->assertStringContainsString('Своя базовая карточка', $viewData['basicCardHtml']);
        $this->assertStringContainsString('Своя премиальная карточка', $viewData['premiumCardHtml']);
        $this->assertStringNotContainsString('<script', $viewData['basicCardHtml']);
        $this->assertStringNotContainsString('onerror', $viewData['premiumCardHtml']);
    }

    public function test_admin_form_contains_two_html_fields_and_button_text(): void
    {
        $definition = app(BlockRegistry::class)->find(BlockType::KIDS_OPTICS_PACKAGES);

        $this->assertSame(
            ['payload.basic_card_html', 'payload.premium_card_html', 'payload.button_text'],
            $this->collectComponentNames($definition->formSchema()),
        );
    }

    public function test_package_visual_assets_are_available_locally(): void
    {
        foreach ([
            'basic-character.avif',
            'basic-character.webp',
            'basic-character.png',
            'premium-character.avif',
            'premium-character.webp',
            'premium-character.png',
            'check-basic.svg',
            'check-premium.svg',
            'discount.svg',
            'frame.svg',
            'lenses.svg',
            'manufacturing.svg',
            'plus-blue.svg',
            'plus-white.svg',
        ] as $asset) {
            $this->assertFileExists(public_path("images/kids-optics/packages/{$asset}"));
        }
    }

    /** @param array<Component> $components */
    private function collectComponentNames(array $components): array
    {
        $names = [];

        foreach ($components as $component) {
            if (method_exists($component, 'getName')) {
                $names[] = $component->getName();
            }

            if (method_exists($component, 'getChildComponents')) {
                $names = [...$names, ...$this->collectComponentNames($component->getChildComponents())];
            }
        }

        return $names;
    }
}
