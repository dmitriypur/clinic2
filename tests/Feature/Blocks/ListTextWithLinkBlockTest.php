<?php

namespace Tests\Feature\Blocks;

use App\Blocks\BlockRegistry;
use App\Enums\BlockType;
use App\Models\Block;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Tests\TestCase;

class ListTextWithLinkBlockTest extends TestCase
{
    public function test_it_renders_existing_document_and_page_link_payload_through_the_registry(): void
    {
        $block = new Block([
            'type' => BlockType::LIST_TEXT_WITH_LINK,
            'title' => 'Документы',
            'settings' => ['title_hidden' => true],
            'payload' => [
                'grid' => [
                    [
                        'text' => 'Сведения об учредителе',
                        'document' => 'docs/founder.pdf',
                        'link' => null,
                    ],
                    [
                        'text' => 'Информация для пациентов',
                        'document' => null,
                        'link' => 'patients',
                    ],
                ],
            ],
        ]);

        $definition = app(BlockRegistry::class)->find(BlockType::LIST_TEXT_WITH_LINK);

        $this->assertNotNull($definition);
        $this->assertSame('components.block.list-text-with-link', $definition->view());

        $viewData = $definition->viewData($block);

        $this->assertSame('/storage/docs/founder.pdf', $viewData['items'][0]['url']);
        $this->assertSame('Открыть документ', $viewData['items'][0]['actionLabel']);
        $this->assertTrue($viewData['items'][0]['newTab']);
        $this->assertStringEndsWith('/patients', $viewData['items'][1]['url']);
        $this->assertSame('Подробнее', $viewData['items'][1]['actionLabel']);
        $this->assertFalse($viewData['items'][1]['newTab']);

        $html = view($definition->view(), [
            'block' => $block,
            ...$viewData,
        ])->render();

        $this->assertStringContainsString('Сведения об учредителе', $html);
        $this->assertStringContainsString('href="/storage/docs/founder.pdf"', $html);
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('Информация для пациентов', $html);
        $this->assertStringContainsString('Подробнее', $html);
        $this->assertStringNotContainsString('Медицинские сотрудники', $html);

        $document = new DOMDocument;
        $previousErrors = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);

        $documentLink = (new DOMXPath($document))
            ->query('//a[@href="/storage/docs/founder.pdf"]')
            ?->item(0);

        $this->assertInstanceOf(DOMElement::class, $documentLink);
        $this->assertSame('div', $documentLink->parentNode?->nodeName);
        $this->assertStringContainsString('bg-surface', $documentLink->parentNode?->attributes?->getNamedItem('class')?->nodeValue ?? '');
        $this->assertStringContainsString('absolute', $documentLink->getAttribute('class'));
        $this->assertStringContainsString('md:static', $documentLink->getAttribute('class'));
        $this->assertStringContainsString('Сведения об учредителе', $documentLink->getAttribute('aria-label'));
    }
}
