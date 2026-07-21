<?php

namespace App\Services;

use App\Models\Block;
use App\Models\Doctor;
use App\Models\Page;
use App\Models\Service;
use App\Search\SiteSearchResult;
use App\Support\CitySeoVariables;
use Illuminate\Database\Eloquent\Builder;
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
        $effectivePhrase = implode(' ', $tokens);

        if ($tokens === [] || $this->length(str_replace(' ', '', $effectivePhrase)) < 2) {
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
                                    ->orWhereRaw("body_html LIKE ? ESCAPE '!'", [$like]);

                                $this->wherePayloadContains($blockQuery, $like);
                            });
                    });
                }
            })
            ->get();

        $pageResults = $pages
            ->map(function (Page $page) use ($effectivePhrase, $tokens): ?SiteSearchResult {
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
                    snippet: $this->snippet($body, $blocks, $effectivePhrase, $tokens),
                    score: $this->score($title, $supportingText, $effectivePhrase, $tokens),
                );
            })
            ->filter();

        $doctorQuery = Doctor::query()->publiclyVisible();

        if ($doctorQuery->getConnection()->getDriverName() !== 'sqlite') {
            $doctorQuery->where(function ($query) use ($tokens): void {
                foreach ($tokens as $token) {
                    $like = '%' . $this->escapeLike($token) . '%';

                    $query->where(function ($tokenQuery) use ($like): void {
                        $tokenQuery->whereRaw("name LIKE ? ESCAPE '!'", [$like])
                            ->orWhereRaw("surname LIKE ? ESCAPE '!'", [$like])
                            ->orWhereRaw("speciality LIKE ? ESCAPE '!'", [$like])
                            ->orWhereRaw("job_title LIKE ? ESCAPE '!'", [$like])
                            ->orWhereRaw("excerpt LIKE ? ESCAPE '!'", [$like])
                            ->orWhereRaw("bio LIKE ? ESCAPE '!'", [$like]);
                    });
                }
            });
        }

        $doctorResults = $doctorQuery
            ->get()
            ->map(function (Doctor $doctor) use ($effectivePhrase, $tokens): ?SiteSearchResult {
                $doctor = $doctor->withResolvedCitySeoVariables();
                $title = $this->plainText($doctor->full_name);
                $supportingText = trim(implode(' ', [
                    $this->plainText($doctor->speciality),
                    $this->plainText($doctor->job_title),
                    $this->plainText($doctor->excerpt),
                    $this->plainText($doctor->bio),
                ]));

                if (! $this->matchesAllTokens($tokens, trim($title . ' ' . $supportingText))) {
                    return null;
                }

                return new SiteSearchResult(
                    key: "doctor:{$doctor->id}",
                    id: (int) $doctor->id,
                    type: 'doctor',
                    typeLabel: 'Врач',
                    title: $title,
                    url: $doctor->url,
                    snippet: $this->entitySnippet([
                        $this->plainText($doctor->speciality),
                        $this->plainText($doctor->job_title),
                        $this->plainText($doctor->excerpt),
                        $this->plainText($doctor->bio),
                    ], $effectivePhrase, $tokens),
                    score: $this->score($title, $supportingText, $effectivePhrase, $tokens) + 10,
                );
            })
            ->filter();

        $serviceQuery = Service::query()
            ->where('is_active', true)
            ->with('parent');

        if ($serviceQuery->getConnection()->getDriverName() !== 'sqlite') {
            $serviceQuery->where(function ($query) use ($tokens): void {
                foreach ($tokens as $token) {
                    $like = '%' . $this->escapeLike($token) . '%';

                    $query->where(function ($tokenQuery) use ($like): void {
                        $tokenQuery->whereRaw("title LIKE ? ESCAPE '!'", [$like])
                            ->orWhereHas('parent', fn (Builder $parentQuery) => $parentQuery->whereRaw("title LIKE ? ESCAPE '!'", [$like]));
                    });
                }
            });
        }

        $serviceResults = $serviceQuery
            ->get()
            ->map(function (Service $service) use ($effectivePhrase, $tokens): ?SiteSearchResult {
                $title = $this->resolvedText($service->title);
                $parentTitle = $this->resolvedText($service->parent?->title);
                $supportingText = $service->parent ? "{$parentTitle}: {$title}" : '';

                if (! $this->matchesAllTokens($tokens, trim($title . ' ' . $supportingText))) {
                    return null;
                }

                $anchor = $service->parent?->uuid ?? $service->uuid;

                return new SiteSearchResult(
                    key: "service:{$service->id}",
                    id: (int) $service->id,
                    type: 'service',
                    typeLabel: 'Услуга',
                    title: $title,
                    url: city_url('/services') . "#{$anchor}",
                    snippet: $service->parent ? "{$parentTitle}: {$title}" : null,
                    score: $this->score($title, $supportingText, $effectivePhrase, $tokens) + 10,
                );
            })
            ->filter();

        return $pageResults
            ->concat($doctorResults)
            ->concat($serviceResults)
            ->unique(fn (SiteSearchResult $result): string => $result->key)
            ->sort(function (SiteSearchResult $left, SiteSearchResult $right): int {
                if ($left->score() !== $right->score()) {
                    return $right->score() <=> $left->score();
                }

                return [$this->lower($left->title), $left->key]
                    <=> [$this->lower($right->title), $right->key];
            })
            ->values();
    }

    private function wherePayloadContains(Builder $query, string $like): void
    {
        if ($query->getConnection()->getDriverName() === 'sqlite') {
            $query->orWhereRaw(
                "EXISTS (SELECT 1 FROM json_tree(blocks.payload) WHERE json_tree.type = 'text' AND json_tree.value LIKE ? ESCAPE '!')",
                [$like],
            );

            return;
        }

        if ($query->getConnection()->getDriverName() === 'mysql') {
            $query->orWhereRaw("JSON_SEARCH(payload, 'one', ?, '!') IS NOT NULL", [$like]);

            return;
        }

        $query->orWhereRaw("payload LIKE ? ESCAPE '!'", [$like]);
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

    private function entitySnippet(array $sources, string $term, array $tokens): ?string
    {
        $source = collect($sources)
            ->filter(fn (string $source): bool => $source !== '')
            ->first(fn (string $source): bool => $this->matchesAllTokens($tokens, $source));
        $source ??= collect($sources)->first(fn (string $source): bool => $source !== '');

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

    private function resolvedText(?string $value): string
    {
        return $this->plainText(app(CitySeoVariables::class)->replace($value));
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
