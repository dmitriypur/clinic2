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
