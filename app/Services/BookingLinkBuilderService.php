<?php

namespace App\Services;

use App\Models\BookingWidgetBranchOrder;
use App\Models\City;
use App\Models\Doctor;
use App\Models\Page;

class BookingLinkBuilderService
{
    public function __construct(
        private readonly CityService $cityService,
        private readonly UtmTrackerService $utmTrackerService,
    ) {
    }

    public function buildUrl(
        City $city,
        ?Page $page,
        string $entry,
        string $targetId,
        ?array $utm = null,
    ): string {
        $query = [
            $this->bookingParameterName($entry) => $targetId,
            'force_city' => $city->slug,
        ];

        foreach ($this->normalizeUtm($utm) as $key => $value) {
            $query[$key] = $value;
        }

        return $this->absoluteUrl($this->buildPath($city, $page)) . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    public function getPageOptions(City $city): array
    {
        $options = [
            '__home' => 'Главная',
        ];

        Page::query()
            ->active()
            ->with('category')
            ->where(function ($query) use ($city): void {
                $query
                    ->whereDoesntHave('cities')
                    ->orWhereHas('cities', fn ($cityQuery) => $cityQuery->whereKey($city->id));
            })
            ->orderBy('title')
            ->get()
            ->each(function (Page $page) use (&$options): void {
                $options[(string) $page->id] = $page->title;
            });

        return $options;
    }

    public function getDoctorOptions(City $city): array
    {
        return Doctor::query()
            ->withoutGlobalScopes()
            ->select(['doctors.id', 'doctors.uuid', 'doctors.surname', 'doctors.name'])
            ->join('city_doctor', 'city_doctor.doctor_id', '=', 'doctors.id')
            ->where('city_doctor.city_id', $city->id)
            ->whereNotNull('doctors.uuid')
            ->where('doctors.uuid', '!=', '')
            ->orderBy('doctors.surname')
            ->orderBy('doctors.name')
            ->get()
            ->mapWithKeys(fn (Doctor $doctor): array => [
                (string) $doctor->uuid => trim($doctor->surname . ' ' . $doctor->name),
            ])
            ->all();
    }

    public function getBranchOptions(City $city): array
    {
        return BookingWidgetBranchOrder::query()
            ->where('city_id', $city->id)
            ->orderByRaw('sort_order IS NULL, sort_order ASC')
            ->orderBy('clinic_name')
            ->orderBy('title')
            ->get()
            ->mapWithKeys(fn (BookingWidgetBranchOrder $branch): array => [
                (string) $branch->branch_id => trim($branch->clinic_name . ' — ' . $branch->title, " \t\n\r\0\x0B—"),
            ])
            ->all();
    }

    public function getUtmOptions(City $city): array
    {
        return collect($this->getUtmRows($city))
            ->mapWithKeys(fn (array $row): array => [
                $row['key'] => $this->utmOptionLabel($row),
            ])
            ->all();
    }

    public function getUtmPayloadByKey(City $city, ?string $key): ?array
    {
        if (! $key) {
            return null;
        }

        return collect($this->getUtmRows($city))->firstWhere('key', $key);
    }

    public function findPageByOption(?string $pageOption): ?Page
    {
        if (! $pageOption || $pageOption === '__home' || ! is_numeric($pageOption)) {
            return null;
        }

        return Page::query()->with('category')->find((int) $pageOption);
    }

    private function getUtmRows(City $city): array
    {
        $state = $this->utmTrackerService->getEditorState($city);
        $sourcesByKey = collect($state['sources'] ?? [])->keyBy('key');

        return collect($state['campaigns'] ?? [])
            ->map(function (array $row) use ($sourcesByKey): ?array {
                $source = $sourcesByKey->get($row['source_key'] ?? null);
                $sourceValue = trim((string) data_get($source, 'source'));

                if ($sourceValue === '') {
                    return null;
                }

                $type = (string) ($row['type'] ?? 'source');
                $medium = in_array($type, ['medium', 'campaign'], true)
                    ? trim((string) ($row['medium'] ?? ''))
                    : null;
                $campaign = $type === 'campaign'
                    ? trim((string) ($row['campaign'] ?? ''))
                    : null;

                return [
                    'key' => $this->utmPayloadKey($sourceValue, $medium, $campaign),
                    'type' => $type,
                    'source' => $sourceValue,
                    'source_name' => data_get($source, 'name'),
                    'medium' => $medium,
                    'medium_name' => in_array($type, ['medium', 'campaign'], true)
                        ? ($row['medium_name'] ?? null)
                        : null,
                    'campaign' => $campaign,
                    'campaign_name' => $type === 'campaign'
                        ? ($row['campaign_name'] ?? null)
                        : null,
                ];
            })
            ->filter()
            ->sortByDesc(fn (array $row): int => match ($row['type']) {
                'campaign' => 3,
                'medium' => 2,
                default => 1,
            })
            ->values()
            ->all();
    }

    private function utmPayloadKey(string $source, ?string $medium, ?string $campaign): string
    {
        return implode('|', [
            $source,
            $medium ?: '',
            $campaign ?: '',
        ]);
    }

    private function buildPath(City $city, ?Page $page): string
    {
        $path = '/';

        if ($page) {
            $page->loadMissing('category');
            $path = '/' . ltrim((string) $page->handle, '/');

            if ($page->category) {
                $path = '/' . trim((string) $page->category->handle, '/') . '/' . ltrim((string) $page->handle, '/');
            }
        }

        if ($city->is_default || $this->cityService->isGlobalPath($path)) {
            return $path;
        }

        return '/' . trim((string) $city->slug, '/') . ($path === '/' ? '' : $path);
    }

    private function bookingParameterName(string $entry): string
    {
        return $entry === 'doctor' ? 'booking_doctor_id' : 'booking_branch_id';
    }

    private function absoluteUrl(string $path): string
    {
        return rtrim((string) config('app.url'), '/') . '/' . ltrim($path, '/');
    }

    private function normalizeUtm(?array $utm): array
    {
        if (! $utm) {
            return [];
        }

        return array_filter([
            'utm_source' => $utm['source'] ?? null,
            'utm_medium' => $utm['medium'] ?? null,
            'utm_campaign' => $utm['campaign'] ?? null,
        ], fn ($value): bool => filled($value));
    }

    public function buildVkUrl(
        City $city,
        string $entry,
        string $targetId,
        ?array $utm = null,
    ): string {
        $params = [
            $this->bookingParameterName($entry) => $targetId,
            'city' => $city->slug,
        ];

        foreach ($this->normalizeUtm($utm) as $key => $value) {
            $params[$key] = $value;
        }

        $hash = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        return rtrim((string) config('app.vk_mini_app_url'), '/') . '#' . $hash;
    }

    private function utmOptionLabel(array $row): string
    {
        $parts = array_filter([
            $row['source'],
            $row['medium'] ?? null,
            $row['campaign'] ?? null,
        ], fn ($value): bool => filled($value));
        $label = implode(' / ', $parts);
        $name = $row['campaign_name'] ?? $row['medium_name'] ?? $row['source_name'] ?? null;

        return filled($name) ? "{$label} ({$name})" : $label;
    }
}
