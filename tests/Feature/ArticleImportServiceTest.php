<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BlockType;
use App\Jobs\ImportArticle;
use App\Models\ArticleImport;
use App\Models\Category;
use App\Models\CuratorMedia;
use App\Models\Doctor;
use App\Services\ArticleImport\ArticleImportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ArticleImportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasColumn('blocks', 'settings')) {
            Schema::table('blocks', function (Blueprint $table): void {
                $table->json('settings')->nullable();
            });
        }
    }

    public function test_import_removes_padding_above_and_below_tags_block(): void
    {
        $result = app(ArticleImportService::class)->import($this->importData());
        $tagsBlock = $result->page->blocks->firstWhere('type', BlockType::TAGS);

        $this->assertNotNull($tagsBlock);
        $this->assertTrue($tagsBlock->settings['remove_top_padding']);
        $this->assertTrue($tagsBlock->settings['remove_bottom_padding']);
    }

    public function test_google_doc_import_alternates_text_block_image_positions(): void
    {
        Http::fake(['docs.google.com/*' => Http::response(<<<'HTML'
            <html><body>
                <h1>Article</h1><p>Lead text.</p>
                <h2>First section</h2><p>First text.</p>
                <h2>Second section</h2><p>Second text.</p>
                <h2>Third section</h2><p>Third text.</p>
            </body></html>
            HTML)]);

        $result = app(ArticleImportService::class)->import($this->importData([
            'document_url' => 'https://docs.google.com/document/d/test-document/edit',
        ]));

        $this->assertSame(
            ['right', 'left', 'right', 'left'],
            $result->page->blocks->where('type', BlockType::POST_TEXT)
                ->map(fn ($block) => $block->payload['image_position'])->values()->all(),
        );
    }

    public function test_google_doc_faq_menu_links_point_to_faq_block(): void
    {
        Http::fake(['docs.google.com/*' => Http::response(<<<'HTML'
            <html><head><meta charset="utf-8"></head><body>
                <h1>Article</h1><p>Lead text.</p>
                <h2>Section</h2><p>Section text.</p>
                <h2>Часто задаваемые вопросы</h2><h3>Question?</h3><p>Answer.</p>
                <h3>Якорь-меню: Вопрос-ответ (заголовок «Часто задаваемые вопросы»), Часто задаваемые вопросы (заголовок «Часто задаваемые вопросы»)</h3>
            </body></html>
            HTML)]);

        $result = app(ArticleImportService::class)->import($this->importData([
            'document_url' => 'https://docs.google.com/document/d/test-document/edit',
        ]));
        $tags = $result->page->blocks->firstWhere('type', BlockType::TAGS);
        $faq = $result->page->blocks->firstWhere('type', BlockType::FAQ);

        $this->assertSame(['#faq', '#faq'], array_column($tags->payload['tags'], 'link'));
        $this->assertNotNull($faq);
        $this->assertSame('faq', $faq->anchor);
    }

    public function test_google_doc_expert_comment_creates_separate_block_with_article_author(): void
    {
        Http::fake(['docs.google.com/*' => Http::response(<<<'HTML'
            <html><head><meta charset="utf-8"></head><body>
                <h1>Article</h1><p>Вступление.</p>
                <h2>Section</h2><p>До цитаты.</p>
                <p><span>Текст <strong>мнения врача</strong>.</span><sup><a href="#cmnt6" id="cmnt_ref6">[f]</a></sup></p>
                <p>После цитаты.</p>
                <div><p><a href="#cmnt_ref6" id="cmnt6">[f]</a>Оформить как блок "Мнение эксперта"</p></div>
            </body></html>
            HTML)]);

        $doctor = $this->createDoctor();
        $media = $this->createCuratorMedia();
        $result = app(ArticleImportService::class)->import($this->importData([
            'document_url' => 'https://docs.google.com/document/d/test-document/edit',
            'author_id' => $doctor->id,
            'expert_curator_image_id' => $media->id,
        ]));

        $expert = $result->page->blocks->firstWhere('type', BlockType::EXPERT_OPINION);
        $this->assertNotNull($expert);
        $this->assertSame($doctor->id, $expert->payload['author']);
        $this->assertSame($media->id, $expert->payload['curator_image_id']);
        $this->assertStringContainsString('мнения врача', $expert->body_html);
        $this->assertStringNotContainsString('[f]', $expert->body_html);
        $text = $result->page->blocks->where('type', BlockType::POST_TEXT)->pluck('body_html')->implode(' ');
        $this->assertStringNotContainsString('мнения врача', $text);
        $this->assertStringContainsString('До цитаты', $text);
        $this->assertStringContainsString('После цитаты', $text);
        $this->assertSame([], $result->warnings);
    }

    public function test_google_doc_expert_opinion_is_preserved_without_an_author(): void
    {
        Http::fake(['docs.google.com/*' => Http::response(<<<'HTML'
            <html><head><meta charset="utf-8"></head><body>
                <h1>Article</h1><p>Вступление.</p>
                <p>Мнение врача.<sup><a href="#cmnt6">[f]</a></sup></p>
                <div><p><a href="#cmnt_ref6">[f]</a>Оформить как блок «Мнение эксперта»</p></div>
            </body></html>
            HTML)]);
        $result = app(ArticleImportService::class)->import($this->importData([
            'document_url' => 'https://docs.google.com/document/d/test-document/edit',
        ]));

        $expert = $result->page->blocks->firstWhere('type', BlockType::EXPERT_OPINION);
        $this->assertNotNull($expert);
        $this->assertStringContainsString('Мнение врача', $expert->body_html);
        $this->assertNotEmpty($result->warnings);
    }

    public function test_import_adds_callback_block_without_padding_before_contacts(): void
    {
        $result = app(ArticleImportService::class)->import($this->importData([
            'append_default_blocks' => true,
        ]));
        $blocks = $result->page->blocks->values();
        $callback = $blocks->get($blocks->count() - 2);

        $this->assertSame(BlockType::BANNER_SPECIALIST_CALLBACK, $callback->type);
        $this->assertSame('Запишитесь на прием онлайн', $callback->title);
        $this->assertSame([], $callback->payload);
        $this->assertTrue($callback->settings['remove_top_padding']);
        $this->assertTrue($callback->settings['remove_bottom_padding']);
        $this->assertSame(BlockType::CONTACTS, $blocks->last()->type);
        $this->assertTrue($blocks->last()->settings['title_hidden']);
        $this->assertTrue($blocks->last()->settings['remove_top_padding']);
        $this->assertTrue($blocks->last()->settings['remove_bottom_padding']);
        $slider = $blocks->firstWhere('type', BlockType::CARDS_SLIDER);
        $this->assertFalse($slider->settings['title_hidden']);
        $this->assertFalse($slider->settings['remove_top_padding']);
        $this->assertFalse($slider->settings['remove_bottom_padding']);
        $this->assertFalse($blocks->contains(fn ($block) => $block->type === BlockType::CALL_TO_ACTION));
    }

    public function test_import_creates_expert_opinion_before_navigation_and_faq(): void
    {
        Storage::fake('public');
        $doctor = $this->createDoctor();
        $media = $this->createCuratorMedia();
        $result = app(ArticleImportService::class)->import(
            $this->importData([
                'include_expert_opinion' => true,
                'expert_id' => $doctor->id,
                'expert_body_html' => '<p>Комментарий врача.</p>',
                'expert_curator_image_id' => $media->id,
            ])
        );

        $this->assertSame(
            [
                BlockType::AUTHOR,
                BlockType::TAGS,
                BlockType::POST_TEXT,
                BlockType::EXPERT_OPINION,
                BlockType::ARTICLE_NAVIGATION,
                BlockType::FAQ,
            ],
            $result->page->blocks->pluck('type')->all(),
        );

        $expertBlock = $result->page->blocks->firstWhere('type', BlockType::EXPERT_OPINION);

        $this->assertSame((string) $doctor->id, (string) $expertBlock->payload['author']);
        $this->assertSame('<p>Комментарий врача.</p>', $expertBlock->body_html);
        $this->assertSame($media->id, $expertBlock->payload['curator_image_id']);
        $this->assertNull($expertBlock->getFirstMedia('default'));
        $this->assertSame([], $result->warnings);
    }

    public function test_import_skips_expert_opinion_and_adds_warning_when_curator_image_is_missing(): void
    {
        $doctor = $this->createDoctor();
        $result = app(ArticleImportService::class)->import(
            $this->importData([
                'include_expert_opinion' => true,
                'expert_id' => $doctor->id,
                'expert_body_html' => '<p>Комментарий врача.</p>',
                'expert_curator_image_id' => 999999,
            ])
        );

        $this->assertFalse(
            $result->page->blocks->contains(
                fn ($block): bool => $block->type === BlockType::EXPERT_OPINION
            )
        );
        $this->assertTrue(
            $result->page->blocks->contains(
                fn ($block): bool => $block->type === BlockType::ARTICLE_NAVIGATION
            )
        );
        $this->assertSame(
            ['Не удалось добавить блок «Мнение эксперта»: проверьте врача, текст и фотографию.'],
            $result->warnings,
        );
    }

    private function createCuratorMedia(): CuratorMedia
    {
        return CuratorMedia::query()->create([
            'disk' => 'public',
            'directory' => 'expert-opinions',
            'visibility' => 'public',
            'name' => 'expert',
            'path' => 'expert-opinions/expert.png',
            'type' => 'image/png',
            'ext' => 'png',
        ]);
    }

    public function test_failed_import_job_deletes_temporary_expert_image(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('article-imports/expert.png', 'temporary image');

        $articleImport = ArticleImport::query()->create([
            'status' => ArticleImport::STATUS_PROCESSING,
            'payload' => [
                'expert_image_path' => 'article-imports/expert.png',
            ],
        ]);

        (new ImportArticle($articleImport->id))->failed(new RuntimeException('Import failure'));

        Storage::disk('local')->assertMissing('article-imports/expert.png');
        $this->assertSame(
            ArticleImport::STATUS_FAILED,
            $articleImport->fresh()->status,
        );
    }

    public function test_import_positions_article_navigation_once_after_all_blocks_are_created(): void
    {
        $updates = [];
        DB::listen(function ($query) use (&$updates): void {
            if (str_starts_with(strtolower(ltrim($query->sql)), 'update "blocks"')) {
                $updates[] = $query->sql;
            }
        });

        $result = app(ArticleImportService::class)->import($this->importData());

        $this->assertCount($result->page->blocks->count(), $updates);
        $this->assertSame(
            [
                BlockType::AUTHOR,
                BlockType::TAGS,
                BlockType::POST_TEXT,
                BlockType::ARTICLE_NAVIGATION,
                BlockType::FAQ,
            ],
            $result->page->blocks->pluck('type')->all(),
        );
    }

    private function importData(array $overrides = []): array
    {
        $category = Category::query()->create([
            'title' => 'Статьи',
            'handle' => 'stati',
        ]);

        return array_merge([
            'source' => <<<'MARKDOWN'
                # Тестовая статья

                ## Первый раздел
                Основной текст.

                ## FAQ
                ### Тестовый вопрос?
                Тестовый ответ.
                MARKDOWN,
            'document_url' => '',
            'category_id' => $category->id,
            'author_id' => null,
            'theme' => '',
            'breadcrumbs_title' => '',
            'append_default_blocks' => false,
            'active' => false,
        ], $overrides);
    }

    private function createDoctor(): Doctor
    {
        return Doctor::query()->create([
            'name' => 'Иван',
            'surname' => 'Иванов',
            'speciality' => 'Офтальмолог',
            'job_title' => 'Врач',
            'bio' => 'Биография',
        ]);
    }
}
