<?php

declare(strict_types=1);

namespace App\Services\ArticleImport;

use Illuminate\Support\Str;
use League\CommonMark\CommonMarkConverter;
use DOMElement;

class ArticleContentParser
{
    private CommonMarkConverter $markdown;

    public function __construct()
    {
        $this->markdown = new CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    public function parseFromGoogleDocsHtml(string $html): array
    {
        $document = new \DOMDocument();

        libxml_use_internal_errors(true);
        $document->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($document);
        $nodes = $xpath->query('//body//*[self::h1 or self::h2 or self::h3 or self::p or self::ul or self::ol or self::img][not(ancestor::ul or ancestor::ol)]');
        $commentAnnotations = $this->extractCommentAnnotations($xpath);

        if ($nodes === false || $nodes->length === 0) {
            throw new ArticleImportException(
                'разбор документа',
                'Не удалось распознать структуру документа Google Docs.',
                'Проверьте, что документ экспортируется в HTML и содержит основной текст статьи.'
            );
        }

        $meta = [
            'title' => null,
            'theme' => null,
            'seo_title' => null,
            'seo_description' => null,
            'tag_titles' => [],
            'handle' => null,
            'menu_items' => [],
            'image_urls' => [],
        ];

        $meta = array_merge($meta, $this->extractGoogleDocsMeta($xpath));

        $articleTitleIndex = null;
        $serviceTitle = null;
        $serviceTitleIndex = null;

        foreach ($nodes as $index => $node) {
            $tag = $this->resolveImportedTag($node, $commentAnnotations);

            if ($tag !== 'h1') {
                continue;
            }

            $title = $this->cleanHeading($node->textContent ?? '');
            if ($title === '') {
                continue;
            }

            if (Str::startsWith(Str::lower($title), 'тз на размещение текста')) {
                $serviceTitle ??= $this->extractTitleFromTaskHeading($title);
                $serviceTitleIndex ??= $index;
                continue;
            }

            $meta['title'] = $title;
            $articleTitleIndex = $index;
            break;
        }

        if ($articleTitleIndex === null) {
            if ($serviceTitle === null) {
                throw new ArticleImportException(
                    'поиск заголовка',
                    'В документе не найден основной заголовок статьи (H1).',
                    'Добавьте заголовок статьи как H1 или укажите его в первой строке вида ТЗ на размещение текста №... "Заголовок".'
                );
            }

            $meta['title'] = $serviceTitle;
            $articleTitleIndex = $serviceTitleIndex ?? -1;
        }

        $sections = [];
        $faqItems = [];
        $currentSection = [
            'title' => $meta['title'],
            'anchor' => null,
            'image_position' => 'right',
            'html_parts' => [],
            'is_lead' => true,
        ];
        $currentFaqQuestion = null;
        $currentFaqParts = [];
        $mode = 'sections';

        foreach ($nodes as $index => $node) {
            if ($index <= $articleTitleIndex) {
                continue;
            }

            $tag = $this->resolveImportedTag($node, $commentAnnotations);
            $text = trim(preg_replace('/\s+/u', ' ', $node->textContent ?? ''));
            $text = $tag === 'h2' || $tag === 'h3' ? $this->cleanHeading($text) : $text;

            if ($text === '' && $tag !== 'img') {
                continue;
            }

            if ($this->shouldSkipPreContentNode($currentSection, $tag, $text)) {
                continue;
            }

            if ($tag === 'h2') {
                $this->flushCurrentSection($sections, $currentSection);
                $this->flushCurrentFaq($faqItems, $currentFaqQuestion, $currentFaqParts);

                if ($this->isFaqHeading($text)) {
                    $mode = 'faq';
                    continue;
                }

                $mode = 'sections';
                $currentSection = [
                    'title' => $text,
                    'anchor' => $this->makeAnchor($text, count($sections) + 1),
                    'image_position' => 'right',
                    'html_parts' => [],
                    'is_lead' => false,
                ];
                continue;
            }

            if ($mode === 'faq') {
                if ($this->isServiceInstructionNode($tag, $text)) {
                    $this->flushCurrentFaq($faqItems, $currentFaqQuestion, $currentFaqParts);
                    break;
                }

                if ($tag === 'h3') {
                    $this->flushCurrentFaq($faqItems, $currentFaqQuestion, $currentFaqParts);
                    $currentFaqQuestion = $text;
                    continue;
                }

                if ($tag === 'p' && $this->looksLikeFaqQuestion($text)) {
                    $this->flushCurrentFaq($faqItems, $currentFaqQuestion, $currentFaqParts);
                    $currentFaqQuestion = $this->cleanHeading($text);
                    continue;
                }

                if ($currentFaqQuestion !== null && in_array($tag, ['p', 'ul', 'ol'], true)) {
                    $currentFaqParts[] = $this->sanitizeImportedHtml($node->ownerDocument?->saveHTML($node) ?: '');
                }

                continue;
            }

            if ($currentSection !== null) {
                if ($this->isServiceInstructionNode($tag, $text)) {
                    break;
                }

                if ($tag === 'h3') {
                    $currentSection['html_parts'][] = '<h3>' . e($text) . '</h3>';
                    continue;
                }

                if ($tag === 'img') {
                    $currentSection['image_position'] = $currentSection['image_position'] === 'none'
                        ? 'right'
                        : $currentSection['image_position'];
                    continue;
                }

                if (in_array($tag, ['p', 'ul', 'ol'], true)) {
                    $currentSection['html_parts'][] = $this->sanitizeImportedHtml($node->ownerDocument?->saveHTML($node) ?: '');
                }
            }
        }

        $this->flushCurrentSection($sections, $currentSection);
        $this->flushCurrentFaq($faqItems, $currentFaqQuestion, $currentFaqParts);

        if (empty($meta['title']) || empty($sections)) {
            throw new ArticleImportException(
                'разбор структуры статьи',
                'В документе не найден заголовок статьи или разделы второго уровня.',
                'Проверьте, что статья содержит H1 и хотя бы один смысловой блок после него.'
            );
        }

        return [
            ...$meta,
            'sections' => $sections,
            'faq_items' => $faqItems,
        ];
    }

    public function parseFromStructuredText(string $source): array
    {
        $source = trim(str_replace("\r\n", "\n", $source));

        if ($source === '') {
            throw new ArticleImportException(
                'ручной импорт',
                'Текст для импорта пустой.'
            );
        }

        $lines = explode("\n", $source);

        $meta = [
            'title' => null,
            'theme' => null,
            'seo_title' => null,
            'seo_description' => null,
            'tag_titles' => [],
            'handle' => null,
            'menu_items' => [],
            'image_urls' => [],
        ];

        $sections = [];
        $faqItems = [];
        $currentSection = null;
        $currentFaq = null;
        $mode = 'meta';

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                if ($mode === 'faq' && $currentFaq !== null) {
                    $currentFaq['answer_markdown'] .= "\n\n";
                } elseif ($currentSection !== null) {
                    $currentSection['markdown'] .= "\n\n";
                }
                continue;
            }

            if (Str::startsWith($trimmed, '# ')) {
                $meta['title'] = trim(Str::after($trimmed, '# '));
                continue;
            }

            if (Str::startsWith($trimmed, '## ')) {
                if ($currentFaq !== null) {
                    $faqItems[] = [
                        'question' => $currentFaq['question'],
                        'answer_html' => $this->markdownToHtml($currentFaq['answer_markdown']),
                    ];
                    $currentFaq = null;
                }

                if ($currentSection !== null) {
                    $sections[] = [
                        'title' => $currentSection['title'],
                        'anchor' => $currentSection['anchor'],
                        'image_position' => $currentSection['image_position'],
                        'body_html' => $this->markdownToHtml($currentSection['markdown']),
                    ];
                }

                $heading = trim(Str::after($trimmed, '## '));
                if ($this->isFaqHeading($heading)) {
                    $mode = 'faq';
                    $currentSection = null;
                    continue;
                }

                $mode = 'sections';
                $currentSection = [
                    'title' => $heading,
                    'anchor' => $this->makeAnchor($heading, count($sections) + 1),
                    'image_position' => 'none',
                    'markdown' => '',
                ];
                continue;
            }

            if ($mode === 'meta') {
                $this->hydrateMetaFromLine($meta, $trimmed);
                continue;
            }

            if ($mode === 'faq') {
                if (Str::startsWith($trimmed, '### ')) {
                    if ($currentFaq !== null) {
                        $faqItems[] = [
                            'question' => $currentFaq['question'],
                            'answer_html' => $this->markdownToHtml($currentFaq['answer_markdown']),
                        ];
                    }

                    $currentFaq = [
                        'question' => trim(Str::after($trimmed, '### ')),
                        'answer_markdown' => '',
                    ];
                    continue;
                }

                if ($currentFaq !== null) {
                    $currentFaq['answer_markdown'] .= ($currentFaq['answer_markdown'] !== '' ? "\n" : '') . $trimmed;
                }
                continue;
            }

            if ($currentSection === null) {
                continue;
            }

            if (preg_match('/^@anchor:\s*(.+)$/ui', $trimmed, $matches) === 1) {
                $currentSection['anchor'] = $this->makeAnchor($matches[1], count($sections) + 1);
                continue;
            }

            if (preg_match('/^@image:\s*(left|right|none)$/ui', $trimmed, $matches) === 1) {
                $currentSection['image_position'] = strtolower($matches[1]);
                continue;
            }

            $currentSection['markdown'] .= ($currentSection['markdown'] !== '' ? "\n" : '') . $trimmed;
        }

        if ($currentSection !== null) {
            $sections[] = [
                'title' => $currentSection['title'],
                'anchor' => $currentSection['anchor'],
                'image_position' => $currentSection['image_position'],
                'body_html' => $this->markdownToHtml($currentSection['markdown']),
            ];
        }

        if ($currentFaq !== null) {
            $faqItems[] = [
                'question' => $currentFaq['question'],
                'answer_html' => $this->markdownToHtml($currentFaq['answer_markdown']),
            ];
        }

        if (empty($meta['title']) || empty($sections)) {
            throw new ArticleImportException(
                'ручной импорт',
                'Для ручного импорта нужен заголовок `# ...` и хотя бы один блок `## ...`.',
                'Начните текст с `# Заголовок`, затем добавляйте блоки через `## Название блока`.'
            );
        }

        return [
            ...$meta,
            'sections' => $sections,
            'faq_items' => $faqItems,
        ];
    }

    private function hydrateMetaFromLine(array &$meta, string $line): void
    {
        if (preg_match('/^URL:\s*(.+)$/ui', $line, $matches) === 1) {
            $meta['handle'] = $this->extractHandleFromUrl(trim($matches[1]));
            return;
        }

        if (preg_match('/^Тема:\s*(.+)$/ui', $line, $matches) === 1) {
            $meta['theme'] = trim($matches[1]);
            return;
        }

        if (preg_match('/^Теги:\s*(.+)$/ui', $line, $matches) === 1) {
            $meta['tag_titles'] = collect(explode(',', $matches[1]))
                ->map(fn(string $tag) => trim($tag))
                ->filter()
                ->values()
                ->all();
            return;
        }

        if (preg_match('/^SEO Title:\s*(.+)$/ui', $line, $matches) === 1) {
            $meta['seo_title'] = trim($matches[1]);
            return;
        }

        if (preg_match('/^SEO Description:\s*(.+)$/ui', $line, $matches) === 1) {
            $meta['seo_description'] = trim($matches[1]);
        }
    }

    private function flushCurrentSection(array &$sections, ?array &$currentSection): void
    {
        if ($currentSection === null) {
            return;
        }

        $bodyHtml = trim(implode("\n", $currentSection['html_parts'] ?? []));

        if ($bodyHtml !== '') {
                $sections[] = [
                    'title' => $currentSection['title'],
                    'anchor' => $currentSection['anchor'],
                    'image_position' => $currentSection['image_position'],
                    'body_html' => $bodyHtml,
                    'is_lead' => (bool) ($currentSection['is_lead'] ?? false),
                ];
            }

        $currentSection = null;
    }

    private function flushCurrentFaq(array &$faqItems, ?string &$question, array &$parts): void
    {
        if ($question === null) {
            return;
        }

        $answerHtml = trim(implode("\n", $parts));

        if ($answerHtml !== '') {
            $faqItems[] = [
                'question' => $question,
                'answer_html' => $answerHtml,
            ];
        }

        $question = null;
        $parts = [];
    }

    private function sanitizeImportedHtml(string $html): string
    {
        $html = preg_replace('/\sclass="[^"]*"/i', '', $html) ?? $html;
        $html = preg_replace('/\sstyle="[^"]*"/i', '', $html) ?? $html;
        $html = preg_replace('/<sup>\s*<a[^>]+href="#cmnt[^"]*"[^>]*>\[[^<]+\]<\/a>\s*<\/sup>/iu', '', $html) ?? $html;
        $html = preg_replace('/<a[^>]+href="#cmnt[^"]*"[^>]*>\[[^<]+\]<\/a>/iu', '', $html) ?? $html;
        $html = preg_replace_callback('/href="([^"]+)"/iu', function (array $matches) {
            return 'href="' . e($this->normalizeGoogleRedirectUrl($matches[1])) . '"';
        }, $html) ?? $html;

        return trim($html);
    }

    private function markdownToHtml(string $markdown): string
    {
        return trim((string) $this->markdown->convert($markdown));
    }

    private function isFaqHeading(string $heading): bool
    {
        $normalized = Str::lower(trim($heading));

        return in_array($normalized, [
            'faq',
            'часто задаваемые вопросы',
        ], true);
    }

    private function looksLikeFaqQuestion(string $text): bool
    {
        $normalized = trim($text);

        if ($normalized === '') {
            return false;
        }

        if ($this->isServiceInstructionParagraph($normalized) || $this->isServiceInstructionHeading($normalized)) {
            return false;
        }

        return Str::endsWith($normalized, '?');
    }

    private function makeAnchor(string $value, int $index): string
    {
        $anchor = Str::slug($value);

        return $anchor !== '' ? $anchor : "section-{$index}";
    }

    private function cleanHeading(string $value): string
    {
        $value = trim(preg_replace('/\s+/u', ' ', $value));
        $value = preg_replace('/\s*\[[a-zа-я]\]\s*$/iu', '', $value) ?? $value;

        return trim($value);
    }

    private function extractCommentAnnotations(\DOMXPath $xpath): array
    {
        $annotations = [];

        foreach ($xpath->query('//body//p') ?: [] as $paragraph) {
            if (! $paragraph instanceof DOMElement) {
                continue;
            }

            $text = trim(preg_replace('/\s+/u', ' ', $paragraph->textContent ?? ''));

            if (preg_match('/^\[([a-zа-я0-9]+)\]\s*(.+)$/iu', $text, $matches) !== 1) {
                continue;
            }

            $annotations[Str::lower($matches[1])] = trim($matches[2]);
        }

        return $annotations;
    }

    private function resolveImportedTag(DOMElement $node, array $commentAnnotations): string
    {
        $tag = strtolower($node->nodeName);

        if ($tag !== 'p') {
            return $tag;
        }

        $text = trim(preg_replace('/\s+/u', ' ', $node->textContent ?? ''));

        if (preg_match('/\[([a-zа-я0-9]+)\]\s*$/iu', $text, $matches) !== 1) {
            return $tag;
        }

        $annotation = Str::lower($commentAnnotations[Str::lower($matches[1])] ?? '');

        return match (true) {
            Str::startsWith($annotation, 'заголовок h1') => 'h1',
            Str::startsWith($annotation, 'заголовок h2') => 'h2',
            Str::startsWith($annotation, 'заголовок h3') => 'h3',
            default => $tag,
        };
    }

    private function extractTitleFromTaskHeading(string $value): ?string
    {
        if (preg_match('/"([^"]+)"/u', $value, $matches) === 1) {
            return trim($matches[1]) ?: null;
        }

        if (preg_match('/«([^»]+)»/u', $value, $matches) === 1) {
            return trim($matches[1]) ?: null;
        }

        return null;
    }

    private function extractGoogleDocsMeta(\DOMXPath $xpath): array
    {
        $meta = [
            'theme' => null,
            'seo_title' => null,
            'seo_description' => null,
            'handle' => null,
            'menu_items' => [],
            'image_urls' => [],
            'tag_titles' => [],
        ];

        foreach ($xpath->query('//h3') ?: [] as $headingNode) {
            $headingText = $this->cleanHeading($headingNode->textContent ?? '');
            $normalized = Str::lower($headingText);

            if (Str::contains($normalized, 'url-адресом')) {
                $meta['handle'] = $this->extractHandleFromUrl($headingText);
                continue;
            }

            if (Str::startsWith($normalized, 'указать темы для статьи:')) {
                $meta['theme'] = trim(Str::after($headingText, ':'));
                $meta['tag_titles'] = collect(explode(',', $meta['theme']))
                    ->map(fn(string $tag) => trim($tag))
                    ->filter()
                    ->values()
                    ->all();
                continue;
            }

            if (Str::startsWith($normalized, 'якорь-меню:')) {
                $meta['menu_items'] = $this->parseMenuInstruction($headingText);
                continue;
            }

            if (Str::contains($normalized, 'заполнить заголовок / meta title')) {
                $meta['seo_title'] = $this->findNextParagraphText($xpath, $headingNode);
                continue;
            }

            if (Str::contains($normalized, 'заполнить описание / meta description')) {
                $meta['seo_description'] = $this->findNextParagraphText($xpath, $headingNode);
                continue;
            }

            if (Str::contains($normalized, 'добавить изображения для статьи')) {
                $meta['image_urls'] = $this->findFollowingLinks($xpath, $headingNode);
            }
        }

        return $meta;
    }

    private function findNextParagraphText(\DOMXPath $xpath, DOMElement $node): ?string
    {
        $next = $xpath->query('following::p[1]', $node)->item(0);
        if (! $next instanceof DOMElement) {
            return null;
        }

        return trim(preg_replace('/\s+/u', ' ', $next->textContent ?? '')) ?: null;
    }

    private function findFollowingLinks(\DOMXPath $xpath, DOMElement $node, ?int $limit = null): array
    {
        $links = [];

        foreach ($xpath->query('following::a[@href]', $node) ?: [] as $linkNode) {
            $href = $linkNode->getAttribute('href');
            $normalized = $this->normalizeGoogleRedirectUrl($href);

            if (! Str::contains($normalized, 'drive.google.com/file/d/')) {
                continue;
            }

            $links[] = $normalized;

            if ($limit !== null && count($links) >= $limit) {
                break;
            }
        }

        return array_values(array_unique($links));
    }

    private function normalizeGoogleRedirectUrl(string $url): string
    {
        $query = parse_url($url, PHP_URL_QUERY);
        if (! is_string($query)) {
            return $url;
        }

        parse_str($query, $params);

        return is_string($params['q'] ?? null) ? $params['q'] : $url;
    }

    private function parseMenuInstruction(string $text): array
    {
        $text = trim(Str::after($text, ':'));
        $chunks = preg_split('/\),\s*/u', $text) ?: [];

        return collect($chunks)
            ->map(function (string $chunk) {
                $chunk = trim($chunk);
                $chunk = rtrim($chunk, ')');

                if (! Str::contains($chunk, '(заголовок')) {
                    return null;
                }

                [$titlePart, $targetPart] = explode('(заголовок', $chunk, 2);
                $title = trim($titlePart);
                $target = trim($targetPart);
                $target = preg_replace('/^«/u', '', $target) ?? $target;
                $target = preg_replace('/»$/u', '', $target) ?? $target;

                return [
                    'title' => $title,
                    'target_title' => $this->cleanHeading($target),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function extractHandleFromUrl(string $value): ?string
    {
        if (preg_match('~https?://[^/\s]+(?:/[^?\s#]+)*/([^/?#\s]+)~u', $value, $matches) !== 1) {
            return null;
        }

        return trim($matches[1]);
    }

    private function isServiceInstructionHeading(string $heading): bool
    {
        $normalized = Str::lower(trim($heading));

        return Str::startsWith($normalized, 'заполнить заголовок / meta title')
            || Str::startsWith($normalized, 'заполнить описание / meta description')
            || Str::startsWith($normalized, 'добавить изображения для статьи')
            || Str::startsWith($normalized, 'настроить 301 редирект');
    }

    private function isServiceInstructionParagraph(string $text): bool
    {
        $normalized = Str::lower(trim($text));
        $normalized = preg_replace('/^\d+\.\s*/u', '', $normalized) ?? $normalized;

        return $this->isServiceInstructionHeading($normalized)
            || Str::startsWith($normalized, 'заголовок h1')
            || Str::startsWith($normalized, 'заголовок h2')
            || Str::startsWith($normalized, 'ссылка на https://');
    }

    private function isServiceInstructionNode(string $tag, string $text): bool
    {
        if (! in_array($tag, ['h3', 'p', 'ol'], true)) {
            return false;
        }

        return $this->isServiceInstructionHeading($text) || $this->isServiceInstructionParagraph($text);
    }

    private function shouldSkipPreContentNode(?array $currentSection, string $tag, string $text): bool
    {
        if ($currentSection === null || ! ($currentSection['is_lead'] ?? false)) {
            return false;
        }

        if (($currentSection['html_parts'] ?? []) !== []) {
            return false;
        }

        if ($tag === 'h1') {
            return true;
        }

        if ($this->isMetadataInstructionNode($tag, $text)) {
            return true;
        }

        return false;
    }

    private function isMetadataInstructionNode(string $tag, string $text): bool
    {
        if (! in_array($tag, ['p', 'ol'], true)) {
            return false;
        }

        $normalized = Str::lower(trim($text));

        return Str::contains($normalized, 'создать новую страницу')
            || Str::contains($normalized, 'указать автора статьи')
            || Str::contains($normalized, 'указать темы для статьи')
            || Str::contains($normalized, 'якорь-меню:')
            || Str::contains($normalized, 'заполнить текст:');
    }
}
