<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class DocumentLinkTest extends TestCase
{
    public function test_button_mode_keeps_the_document_button_visible_on_mobile(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-document-link
                href="/storage/docs/license.pdf"
                aria-label="Открыть лицензию"
                mobile="button"
                desktop-at="md"
            />
        BLADE);

        $this->assertStringContainsString('href="/storage/docs/license.pdf"', $html);
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('aria-label="Открыть лицензию"', $html);
        $this->assertStringContainsString('inline-flex w-full', $html);
        $this->assertStringContainsString('md:w-auto', $html);
        $this->assertStringNotContainsString('absolute inset-0', $html);
        $this->assertStringContainsString('Открыть документ', $html);
    }

    public function test_card_mode_covers_the_card_on_mobile_and_becomes_a_button_at_the_configured_breakpoint(): void
    {
        $mdHtml = Blade::render(<<<'BLADE'
            <x-document-link href="/storage/docs/rules.pdf" mobile="card" desktop-at="md" />
        BLADE);
        $lgHtml = Blade::render(<<<'BLADE'
            <x-document-link href="/storage/docs/rules.pdf" mobile="card" desktop-at="lg" />
        BLADE);

        $this->assertStringContainsString('absolute inset-0', $mdHtml);
        $this->assertStringContainsString('md:static', $mdHtml);
        $this->assertStringContainsString('hidden md:inline', $mdHtml);

        $this->assertStringContainsString('absolute inset-0', $lgHtml);
        $this->assertStringContainsString('lg:static', $lgHtml);
        $this->assertStringContainsString('hidden lg:inline', $lgHtml);
    }
}
