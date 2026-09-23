<?php

namespace Tests\Feature\Blocks;

use DOMDocument;
use DOMXPath;
use Tests\TestCase;

class LicenseBlockHtmlTest extends TestCase
{
    public function test_license_block_keeps_document_link_without_embedding_unused_gallery(): void
    {
        $block = (object) [
            'title' => 'Лицензии клиники',
            'licenses' => [
                ['src' => '/storage/license-full-size.jpg', 'thumb' => '/storage/license-thumb.jpg'],
            ],
        ];

        $html = view('components.block.licenses', ['block' => $block])->render();

        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        $xpath = new DOMXPath($document);
        $links = $xpath->query('//a[contains(@href, "licenzii-i-iuridiceskaia-informaciia")]');

        $this->assertCount(1, $links);
        $this->assertSame('Посмотреть лицензии', trim($links->item(0)->textContent));
        $this->assertCount(0, $xpath->query('//light-box'));
        $this->assertCount(0, $xpath->query('//button[contains(normalize-space(.), "Посмотреть лицензии")]'));
        $this->assertStringNotContainsString('/storage/license-full-size.jpg', $html);
        $this->assertStringContainsString('/images/blocks/licenses.png', $html);
    }
}
