<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\FrameGender;
use App\Models\CuratorMedia;
use App\Models\Frame;
use App\Models\FrameAgeGroup;
use Illuminate\Database\Eloquent\Builder;

class FrameCatalogService
{
    public const INITIAL_LIMIT = 6;
    public const MAX_LIMIT = 12;

    /** @return array{frames: list<array<string, mixed>>, total: int, hasMore: bool, nextOffset: int} */
    public function page(array $ages = [], array $genders = [], int $offset = 0, int $limit = self::INITIAL_LIMIT): array
    {
        $ages = collect($ages)->map(fn ($age): int => (int) $age)->filter()->unique()->values()->all();
        $genders = collect($genders)
            ->filter(fn ($gender): bool => in_array($gender, FrameGender::values(), true))
            ->unique()
            ->values()
            ->all();
        $offset = max(0, $offset);
        $limit = max(1, min(self::MAX_LIMIT, $limit));

        $query = Frame::query()
            ->publicCatalog()
            ->whereHas('brand', fn (Builder $query): Builder => $query->where('is_active', true))
            ->whereHas('ageGroups', fn (Builder $query): Builder => $query->where('is_active', true))
            ->with([
                'brand',
                'curatorMedia',
                'colors' => fn ($query) => $query->activeOrdered(),
                'ageGroups' => fn ($query) => $query->activeOrdered(),
            ]);

        if ($ages !== []) {
            $query->whereHas('ageGroups', function (Builder $query) use ($ages): Builder {
                return $query->where('is_active', true)->whereIn('frame_age_groups.id', $ages);
            });
        }

        if (count($genders) === 1) {
            $query->whereJsonContains('genders', $genders[0]);
        }

        $total = (clone $query)->count();
        $frames = $query
            ->skip($offset)
            ->take($limit)
            ->get()
            ->map(fn (Frame $frame): array => $this->present($frame))
            ->values()
            ->all();
        $nextOffset = $offset + count($frames);

        return [
            'frames' => $frames,
            'total' => $total,
            'hasMore' => $nextOffset < $total,
            'nextOffset' => $nextOffset,
        ];
    }

    /** @return array<int, string> */
    public function ageFilters(): array
    {
        return FrameAgeGroup::query()->activeOrdered()->pluck('name', 'id')->all();
    }

    /** @param list<array<string, mixed>> $frames */
    public function renderCards(array $frames): string
    {
        return collect($frames)
            ->map(fn (array $frame): string => view(
                'components.block.partials.kids-optics-frame-card',
                ['frame' => $frame],
            )->render())
            ->implode('');
    }

    /** @return array<string, mixed> */
    private function present(Frame $frame): array
    {
        $genders = $frame->genders;

        return [
            'id' => $frame->getKey(),
            'title' => trim("{$frame->brand->name} {$frame->model}"),
            'description' => trim((string) $frame->description),
            'ageGroups' => $frame->ageGroups->modelKeys(),
            'genders' => $genders,
            'gender' => count($genders) === 1 ? $genders[0] : 'unisex',
            'genderLabel' => $frame->genderLabel(),
            'colors' => $frame->colors->pluck('hex')->all(),
            'isHit' => $frame->is_hit,
            'isSchoolChoice' => $frame->is_school_choice,
            'image' => $this->imageSources($frame->curatorMedia, $frame->getKey()),
        ];
    }

    /** @return array{avif: ?string, webp: ?string, webpSrcset: ?string, src: string, srcset: ?string} */
    private function imageSources(?CuratorMedia $media, int $frameId): array
    {
        if ($media !== null && $media->resizable) {
            $webp400 = $media->getSignedUrl(['w' => 400, 'h' => 313, 'fit' => 'crop', 'fm' => 'webp']);
            $webp800 = $media->getSignedUrl(['w' => 800, 'h' => 626, 'fit' => 'crop', 'fm' => 'webp']);
            $jpg400 = $media->getSignedUrl(['w' => 400, 'h' => 313, 'fit' => 'crop', 'fm' => 'jpg']);
            $jpg800 = $media->getSignedUrl(['w' => 800, 'h' => 626, 'fit' => 'crop', 'fm' => 'jpg']);

            return [
                'avif' => null,
                'webp' => null,
                'webpSrcset' => "{$webp400} 400w, {$webp800} 800w",
                'src' => $jpg800,
                'srcset' => "{$jpg400} 400w, {$jpg800} 800w",
            ];
        }

        $fallbackImage = (($frameId - 1) % 3) + 1;
        $basePath = "images/kids-optics/frame-catalog/card-{$fallbackImage}";

        return [
            'avif' => asset("{$basePath}.avif"),
            'webp' => asset("{$basePath}.webp"),
            'webpSrcset' => null,
            'src' => asset("{$basePath}.jpg"),
            'srcset' => null,
        ];
    }
}
