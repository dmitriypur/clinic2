<?php

declare(strict_types=1);

namespace App\Services\ArticleImport;

use App\Enums\BlockType;
use App\Enums\PageType;
use App\Models\Block;
use App\Models\Page;
use App\Models\Tag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class ArticleImportService
{
    public function __construct(
        private readonly GoogleDocsFetcher $googleDocsFetcher,
        private readonly ArticleContentParser $parser,
        private readonly GoogleDriveImageImporter $imageImporter,
    ) {}

    public function import(array $data): ArticleImportResult
    {
        $parsed = $this->parseSource($data);

        return DB::transaction(function () use ($data, $parsed) {
            $page = Page::query()->create([
                'title' => $parsed['title'],
                'breadcrumbs_title' => $data['breadcrumbs_title'] ?: $parsed['title'],
                'handle' => $parsed['handle'] ?: Str::slug($parsed['title']),
                'body_html' => $parsed['sections'][0]['body_html'] ?? null,
                'seo' => array_filter([
                    'title' => $parsed['seo_title'] ?: null,
                    'description' => $parsed['seo_description'] ?: null,
                ]),
                'active' => (bool) ($data['active'] ?? false),
                'type' => PageType::Posts,
                'category_id' => $data['category_id'],
            ]);

            $tags = $this->resolveTags($parsed['tag_titles'] ?? []);
            if ($tags !== []) {
                $page->tags()->sync(collect($tags)->pluck('id')->all());
            }

            $order = 1;

            $this->createAuthorBlock($page, $data, $parsed, $order++);
            $this->createTagsBlock($page, $parsed, $order++);
            $createdPostTextBlocks = $this->createPostTextBlocks($page, $parsed, $order);
            $order += count($parsed['sections']);

            $warnings = $this->attachImportedImages($createdPostTextBlocks, $parsed['image_urls'] ?? [], $page);

            if (!empty($parsed['faq_items'])) {
                Block::query()->create([
                    'page_id' => $page->id,
                    'type' => BlockType::FAQ,
                    'title' => 'Часто задаваемые вопросы',
                    'order_column' => $order++,
                    'payload' => [
                        'faq' => $parsed['faq_items'],
                    ],
                    'settings' => $this->defaultSettings(),
                ]);
            }

            if ($data['append_default_blocks'] ?? true) {
                $this->appendDefaultBlocks($page, $order);
            }

            return new ArticleImportResult(
                page: $page->fresh(['blocks', 'tags', 'category']),
                warnings: $warnings,
            );
        });
    }

    private function parseSource(array $data): array
    {
        $documentUrl = trim((string) ($data['document_url'] ?? ''));
        $source = trim((string) ($data['source'] ?? ''));

        if ($documentUrl === '' && $source === '') {
            throw new ArticleImportException(
                'подготовка импорта',
                'Укажите ссылку на Google Docs или вставьте текст статьи.'
            );
        }

        if ($documentUrl !== '') {
            try {
                $html = $this->googleDocsFetcher->fetch($documentUrl);

                return $this->parser->parseFromGoogleDocsHtml($html);
            } catch (ArticleImportException $exception) {
                throw $exception;
            } catch (Throwable $exception) {
                throw new ArticleImportException(
                    'импорт из Google Docs',
                    'Не удалось обработать документ Google Docs.',
                    'Проверьте структуру документа: H1, H2, FAQ, SEO-блоки и ссылки на изображения.',
                    $exception
                );
            }
        }

        try {
            return $this->parser->parseFromStructuredText($source);
        } catch (ArticleImportException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new ArticleImportException(
                'ручной импорт',
                'Не удалось обработать вставленный текст.',
                'Проверьте формат ручного импорта: # для заголовка, ## для блоков, ### для вопросов FAQ.',
                $exception
            );
        }
    }

    private function createAuthorBlock(Page $page, array $data, array $parsed, int $order): void
    {
        $theme = trim((string) ($data['theme'] ?? $parsed['theme'] ?? ''));

        Block::query()->create([
            'page_id' => $page->id,
            'type' => BlockType::AUTHOR,
            'title' => 'Автор и тема статьи',
            'order_column' => $order,
            'payload' => array_filter([
                'author' => $data['author_id'] ?: null,
                'theme' => $theme !== '' ? $theme : null,
                'url' => '',
            ], fn($value, string $key) => $key === 'url' || ($value !== null && $value !== ''), ARRAY_FILTER_USE_BOTH),
            'settings' => $this->defaultSettings(),
        ]);
    }

    private function createTagsBlock(Page $page, array $parsed, int $order): void
    {
        $sectionsByTitle = collect($parsed['sections'])
            ->filter(fn(array $section) => !empty($section['anchor']))
            ->keyBy(fn(array $section) => mb_strtolower($section['title']));

        $tagLinks = collect($parsed['menu_items'] ?? [])
            ->map(function (array $item) use ($sectionsByTitle) {
                $section = $sectionsByTitle->get(mb_strtolower($item['target_title']));

                return [
                    'title' => $item['title'],
                    'link' => '#' . ($section['anchor'] ?? Str::slug($item['target_title'])),
                ];
            })
            ->filter(fn(array $item) => !empty($item['title']) && !empty($item['link']))
            ->values()
            ->all();

        if ($tagLinks === []) {
            $tagLinks = collect($parsed['sections'])
                ->filter(fn(array $section) => !($section['is_lead'] ?? false) && !empty($section['anchor']))
                ->map(fn(array $section) => [
                    'title' => $section['title'],
                    'link' => '#' . $section['anchor'],
                ])
                ->values()
                ->all();
        }

        Block::query()->create([
            'page_id' => $page->id,
            'type' => BlockType::TAGS,
            'title' => 'Тэги',
            'order_column' => $order,
            'payload' => [
                'tags' => $tagLinks,
            ],
            'settings' => $this->defaultSettings([
                'title_hidden' => true,
            ]),
        ]);
    }

    private function createPostTextBlocks(Page $page, array $parsed, int $startOrder): array
    {
        $blocks = [];

        foreach ($parsed['sections'] as $index => $section) {
            $isLead = (bool) ($section['is_lead'] ?? false);

            $blocks[] = Block::query()->create([
                'page_id' => $page->id,
                'type' => BlockType::POST_TEXT,
                'title' => $section['title'],
                'anchor' => $section['anchor'],
                'body_html' => $section['body_html'],
                'order_column' => $startOrder + $index,
                'payload' => [
                    'bg_block' => 'bg-surface',
                    'image_position' => $section['image_position'] ?? 'right',
                ],
                'settings' => $this->defaultSettings([
                    'title_hidden' => $isLead,
                    'show_page_title' => $isLead,
                    'remove_top_padding' => $isLead,
                    'background' => $isLead ? '1' : null,
                ]),
            ]);
        }

        return $blocks;
    }

    private function appendDefaultBlocks(Page $page, int $startOrder): void
    {
        $blocks = [
            [
                'type' => BlockType::CARDS_SLIDER,
                'title' => 'Читайте также',
                'payload' => [
                    'is_blog' => true,
                    'count_visible' => 3,
                ],
            ],
            [
                'type' => BlockType::CALL_TO_ACTION,
                'title' => 'Запишитесь на прием онлайн',
                'payload' => [
                    'add_fox' => true,
                    'add_fox2' => true,
                    'subtitle' => 'Оставьте ваши контакты, мы перезвоним вам и подтвердим запись',
                ],
            ],
            [
                'type' => BlockType::CONTACTS,
                'title' => 'Контакты',
                'payload' => [],
            ],
        ];

        foreach ($blocks as $offset => $block) {
            Block::query()->create([
                'page_id' => $page->id,
                'type' => $block['type'],
                'title' => $block['title'],
                'order_column' => $startOrder + $offset,
                'payload' => $block['payload'],
                'settings' => $this->defaultSettings(),
            ]);
        }
    }

    private function resolveTags(array $tagTitles): array
    {
        $normalizedTags = collect($tagTitles)
            ->map(fn(string $title) => trim($title))
            ->filter()
            ->mapWithKeys(fn(string $title) => [Str::slug($title) => $title]);

        if ($normalizedTags->isEmpty()) {
            return [];
        }

        $handles = $normalizedTags->keys()->all();
        $existingTags = Tag::query()
            ->whereIn('handle', $handles)
            ->get()
            ->keyBy('handle');

        $missingRows = $normalizedTags
            ->reject(fn(string $title, string $handle) => $existingTags->has($handle))
            ->map(fn(string $title, string $handle) => [
                'handle' => $handle,
                'title' => $title,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->values()
            ->all();

        if ($missingRows !== []) {
            Tag::query()->upsert($missingRows, ['handle'], ['title', 'updated_at']);
        }

        return Tag::query()
            ->whereIn('handle', $handles)
            ->get()
            ->values()
            ->all();
    }

    private function attachImportedImages(array $blocks, array $imageUrls, Page $page): array
    {
        $warnings = [];

        foreach (array_values($imageUrls) as $index => $imageUrl) {
            $block = $blocks[$index] ?? null;

            if (! $block instanceof Block || trim((string) $imageUrl) === '') {
                continue;
            }

            try {
                $this->imageImporter->attachToBlock($block, $imageUrl);

                if ($index === 0) {
                    $this->imageImporter->attachToPage($page, $imageUrl);
                }
            } catch (Throwable $exception) {
                report($exception);

                $warnings[] = 'Не удалось загрузить изображение для блока #' . ($index + 1) . '.';
            }
        }

        return $warnings;
    }

    private function defaultSettings(array $overrides = []): array
    {
        return array_merge([
            'background' => null,
            'breadcrumbs' => false,
            'title_hidden' => false,
            'show_on_mobile' => true,
            'hide_on_desctop' => false,
            'show_page_title' => false,
            'remove_top_padding' => false,
            'remove_bottom_padding' => false,
        ], $overrides);
    }
}
