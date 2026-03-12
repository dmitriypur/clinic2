<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ArticleImport\ArticleContentParser;
use Tests\TestCase;

class ArticleContentParserTest extends TestCase
{
    public function test_it_skips_service_intro_when_title_is_taken_from_task_heading(): void
    {
        $html = <<<'HTML'
            <html>
                <head><meta charset="utf-8"></head>
                <body>
                    <h1>ТЗ на размещение текста №1 "Article title"</h1>
                    <ol>
                        <li>Создать новую страницу в разделе «Статьи»</li>
                    </ol>
                    <p>First paragraph.</p>
                    <h2>Section</h2>
                    <p>Section body.</p>
                </body>
            </html>
        HTML;

        $parsed = app(ArticleContentParser::class)->parseFromGoogleDocsHtml($html);

        $this->assertSame('Article title', $parsed['title']);
        $this->assertStringNotContainsString('Создать новую страницу', $parsed['sections'][0]['body_html']);
        $this->assertStringContainsString('First paragraph.', $parsed['sections'][0]['body_html']);
    }

    public function test_it_builds_faq_from_plain_question_paragraphs(): void
    {
        $html = <<<'HTML'
            <html>
                <head><meta charset="utf-8"></head>
                <body>
                    <h1>Article title</h1>
                    <p>Lead paragraph.</p>
                    <h2>FAQ</h2>
                    <p>How long does adaptation take?</p>
                    <p>From 3 to 7 days.</p>
                    <p>Who should not use lenses?</p>
                    <p>People with contraindications.</p>
                    <ol>
                        <li>Заполнить заголовок / meta title</li>
                    </ol>
                </body>
            </html>
        HTML;

        $parsed = app(ArticleContentParser::class)->parseFromGoogleDocsHtml($html);

        $this->assertCount(2, $parsed['faq_items']);
        $this->assertSame('How long does adaptation take?', $parsed['faq_items'][0]['question']);
        $this->assertStringContainsString('From 3 to 7 days.', $parsed['faq_items'][0]['answer_html']);
        $this->assertStringNotContainsString('meta title', $parsed['faq_items'][1]['answer_html']);
    }

    public function test_it_preserves_lists_and_links_in_google_docs_html(): void
    {
        $html = <<<'HTML'
            <html>
                <head><meta charset="utf-8"></head>
                <body>
                    <h1>Article title</h1>
                    <p>Lead with <a href="https://www.google.com/url?q=https://example.com/article&sa=D">link</a>.</p>
                    <h2>Section</h2>
                    <ol start="1">
                        <li>First item</li>
                    </ol>
                    <ul>
                        <li>Bullet item</li>
                    </ul>
                </body>
            </html>
        HTML;

        $parsed = app(ArticleContentParser::class)->parseFromGoogleDocsHtml($html);

        $this->assertStringContainsString('<a href="https://example.com/article">', $parsed['sections'][0]['body_html']);
        $this->assertStringContainsString('<ol', $parsed['sections'][1]['body_html']);
        $this->assertStringContainsString('<ul', $parsed['sections'][1]['body_html']);
    }
}
