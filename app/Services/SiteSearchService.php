<?php

namespace App\Services;

use App\Models\Block;
use App\Models\Page;
use App\Search\SiteSearchResult;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SiteSearchService
{
    public function search(string $term, int $perPage = 30, int $page = 1): LengthAwarePaginator
    {
        $term = $this->normalize($term);
        $results = $this->results($term);
        $perPage = max(1, $perPage);
        $page = max(1, $page);

        return new LengthAwarePaginator(
            $results->forPage($page, $perPage)->values(),
            $results->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => $term === '' ? [] : ['q' => $term],
            ],
        );
    }

    public function suggest(string $term, int $limit = 5): Collection
    {
        return $this->results($this->normalize($term))
            ->take(max(1, $limit))
            ->values();
    }

    private function results(string $term): Collection
    {
        $tokens = $this->effectiveTokens($term);

        if ($tokens === [] || $this->length(implode('', $tokens)) < 2) {
            return collect();
        }

        $pages = Page::query()
            ->active()
            ->with('blocks')
            ->where(function ($query) use ($tokens): void {
                foreach ($tokens as $token) {
                    $like = '%' . $this->escapeLike($token) . '%';

                    $query->where(function ($tokenQuery) use ($like): void {
                        $tokenQuery->whereRaw("title LIKE ? ESCAPE '!'", [$like])
                            ->orWhereRaw("body_html LIKE ? ESCAPE '!'", [$like])
                            ->orWhereHas('blocks', function ($blockQuery) use ($like): void {
                                $blockQuery->whereRaw("title LIKE ? ESCAPE '!'", [$like])
                                    ->orWhereRaw("body_html LIKE ? ESCAPE '!'", [$like])
                                    ->orWhereRaw("payload LIKE ? ESCAPE '!'", [$like])
                                    ->orWhereNotNull('payload');
                            });
                    });
                }
            })
            ->get();

        return $pages
            ->map(function (Page $page) use ($term, $tokens): ?SiteSearchResult {
                $page = $page->withResolvedCitySeoVariables();
                $title = $this->plainText($page->title);
                $body = $this->plainText($page->body_html);
                $blocks = $page->blocks->map(fn (Block $block): string => $this->blockText($block))->filter()->values();
                $supportingText = trim(implode(' ', [$body, $blocks->implode(' ')]));

                if (! $this->matchesAllTokens($tokens, trim($title . ' ' . $supportingText))) {
                    return null;
                }

                return new SiteSearchResult(
                    key: "page:{$page->id}",
                    id: (int) $page->id,
                    type: 'page',
                    typeLabel: 'Страница',
                    title: $title,
                    url: $page->getUrl(),
                    snippet: $this->snippet($body, $blocks, $term, $tokens),
                    score: $this->score($title, $supportingText, $term, $tokens),
                );
            })
            ->filter()
            ->sort(function (SiteSearchResult $left, SiteSearchResult $right): int {
                if ($left->score() !== $right->score()) {
                    return $right->score() <=> $left->score();
                }

                return [$this->lower($left->title), $left->key]
                    <=> [$this->lower($right->title), $right->key];
            })
            ->values();
    }

    private function effectiveTokens(string $term): array
    {
        $term = preg_replace('/\+?\d(?:[\s().-]*\d){6,}/u', ' ', $term) ?? $term;
        preg_match_all('/[\p{L}\p{N}%_!]+(?:[\'’-][\p{L}\p{N}%_!]+)*/u', $this->lower($term), $matches);
        $cityTokens = $this->cityTokens();

        return array_values(array_unique(array_filter($matches[0], function (string $token) use ($cityTokens): bool {
            return ! in_array($token, $cityTokens, true)
                && ! preg_match('/\d(?:\D*\d){6,}/u', $token);
        })));
    }

    private function cityTokens(): array
    {
        $city = app(CityService::class)->getCurrentCity();

        if (! $city) {
            return [];
        }

        $values = [$city->name, ...array_filter((array) $city->seo_cases, 'is_string')];
        $tokens = [];

        foreach ($values as $value) {
            preg_match_all('/[\p{L}\p{N}]+/u', $this->lower((string) $value), $matches);
            $tokens = [...$tokens, ...$matches[0]];
        }

        return array_values(array_unique($tokens));
    }

    private function score(string $title, string $supportingText, string $term, array $tokens): int
    {
        $title = $this->lower($title);
        $supportingText = $this->lower($supportingText);
        $phrase = $this->lower($term);
        $titleHasAllTokens = $this->matchesAllTokens($tokens, $title);

        return match (true) {
            $title === $phrase => 700,
            str_starts_with($title, $phrase) => 600,
            str_contains($title, $phrase) => 500,
            $titleHasAllTokens => 400,
            $this->matchesSomeTokens($tokens, $title) => 300,
            str_contains($supportingText, $phrase) => 200,
            default => 100,
        };
    }

    private function snippet(string $body, Collection $blocks, string $term, array $tokens): ?string
    {
        $source = $body !== ''
            ? $body
            : $blocks->first(fn (string $block): bool => $this->matchesAllTokens($tokens, $block));
        $source ??= $blocks->first();

        if (! is_string($source) || $source === '') {
            return null;
        }

        $position = $this->matchPosition($source, $term, $tokens);
        $start = max(0, $position - 60);
        $snippet = trim($this->substr($source, $start, 180));

        return $start > 0 ? '…' . $snippet : $snippet;
    }

    private function matchPosition(string $text, string $term, array $tokens): int
    {
        $lowerText = $this->lower($text);
        $position = mb_stripos($lowerText, $this->lower($term), 0, 'UTF-8');

        if ($position !== false) {
            return $position;
        }

        foreach ($tokens as $token) {
            $position = mb_stripos($lowerText, $token, 0, 'UTF-8');
            if ($position !== false) {
                return $position;
            }
        }

        return 0;
    }

    private function blockText(Block $block): string
    {
        return trim(implode(' ', [
            $this->plainText($block->title),
            $this->plainText($block->body_html),
            ...$this->payloadStrings($block->payload),
        ]));
    }

    private function payloadStrings(mixed $value): array
    {
        if (is_string($value)) {
            return [$this->plainText($value)];
        }

        if (! is_array($value)) {
            return [];
        }

        $items = array_values(array_map(fn (mixed $item): array => $this->payloadStrings($item), $value));

        return $items === [] ? [] : array_merge(...$items);
    }

    private function plainText(?string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    private function matchesAllTokens(array $tokens, string $text): bool
    {
        $text = $this->lower($text);

        foreach ($tokens as $token) {
            if (! str_contains($text, $token)) {
                return false;
            }
        }

        return true;
    }

    private function matchesSomeTokens(array $tokens, string $text): bool
    {
        $text = $this->lower($text);

        foreach ($tokens as $token) {
            if (str_contains($text, $token)) {
                return true;
            }
        }

        return false;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }

    private function normalize(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    private function lower(string $value): string
    {
        return mb_strtolower($value, 'UTF-8');
    }

    private function length(string $value): int
    {
        return mb_strlen($value, 'UTF-8');
    }

    private function substr(string $value, int $start, int $length): string
    {
        return mb_substr($value, $start, $length, 'UTF-8');
    }
}
