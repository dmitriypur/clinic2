<?php

namespace App\Services;

use App\Models\City;
use App\Models\CityUtmCampaign;
use App\Models\CityUtmMedium;
use App\Models\CityUtmPhone;
use App\Models\CityUtmSource;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UtmTrackerService
{
    public function emptyEditorState(): array
    {
        return [
            'phones' => [],
            'sources' => [],
            'campaigns' => [],
            'archived_campaigns' => [],
        ];
    }

    public function getEditorState(?City $city): array
    {
        if (! $city?->exists) {
            return $this->emptyEditorState();
        }

        $city->loadMissing([
            'utmPhones',
            'utmSources.defaultPhone',
            'utmCampaigns.source',
            'utmCampaigns.phone',
        ]);

        if (
            $city->utmPhones->isEmpty() &&
            $city->utmSources->isEmpty() &&
            $city->utmCampaigns->isEmpty()
        ) {
            return $this->synchronizeSourceOnlyCampaignRows(
                $this->buildEditorStateFromLegacy($city->utm_phones ?? [])
            );
        }

        if (
            $city->utmCampaigns->isEmpty() &&
            ($city->utmSources->isNotEmpty() || $city->utmMediums()->exists())
        ) {
            return $this->synchronizeSourceOnlyCampaignRows(
                $this->buildEditorStateFromTransitionTables($city)
            );
        }

        return $this->synchronizeSourceOnlyCampaignRows($this->dropConflictingSourceCampaignPhones([
            'phones' => $city->utmPhones
                ->sortBy('phone', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->map(fn (CityUtmPhone $phone): array => [
                    'key' => $this->phoneKey($phone->id),
                    'id' => $phone->id,
                    'phone' => $phone->phone,
                ])
                ->all(),
            'sources' => $city->utmSources
                ->sortBy('source', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->map(fn (CityUtmSource $source): array => [
                    'key' => $this->sourceKey($source->id),
                    'id' => $source->id,
                    'source' => $source->source,
                    'name' => $source->name,
                    'default_phone_key' => $source->default_phone_id ? $this->phoneKey($source->default_phone_id) : null,
                    'open_booking_widget' => (bool) $source->open_booking_widget,
                ])
                ->all(),
            'campaigns' => $this->mapCampaignStateRows(
                $this->activeCampaigns($city)->sortBy(fn (CityUtmCampaign $campaign): string => $this->campaignSortValue($campaign))
            ),
            'archived_campaigns' => $this->mapCampaignStateRows(
                $this->archivedCampaigns($city)->sortByDesc(fn (CityUtmCampaign $campaign): int => $campaign->archived_at?->timestamp ?? 0)
            ),
        ]));
    }

    public function sync(City $city, array $state): void
    {
        $state = $this->synchronizeSourceOnlyCampaignRows($this->normalizeState($state));
        $this->validateState($city, $state);

        $phoneSync = $this->syncPhones($city, $state['phones']);
        $sourceSync = $this->syncSources($city, $state['sources'], $phoneSync['key_to_id']);
        $this->syncCampaigns(
            $city,
            $state['campaigns'],
            $state['archived_campaigns'],
            $sourceSync['key_to_id'],
            $phoneSync['key_to_id'],
        );

        $this->deleteMissingSources($city, $sourceSync['kept_ids']);
        $this->deleteMissingPhones($city, $phoneSync['kept_ids']);

        $city->update([
            'utm_phones' => $this->buildLegacyRules(
                $city->fresh(['utmCampaigns.source', 'utmCampaigns.phone'])
            ),
        ]);
    }

    public function buildLegacyRules(City $city): array
    {
        $city->loadMissing(['utmCampaigns.source', 'utmCampaigns.phone']);

        return $this->activeCampaigns($city)
            ->groupBy('source_id')
            ->map(function (Collection $campaigns): array {
                /** @var CityUtmCampaign|null $sourceOnly */
                $sourceOnly = $campaigns->first(fn (CityUtmCampaign $campaign): bool => ! filled($campaign->medium));
                /** @var CityUtmSource|null $source */
                $source = $sourceOnly?->source ?? $campaigns->first()?->source;

                return [
                    'source' => $source?->source,
                    'phone' => $sourceOnly?->phone?->phone,
                    'medium' => $campaigns
                        ->filter(fn (CityUtmCampaign $campaign): bool => filled($campaign->medium))
                        ->sortBy('medium', SORT_NATURAL | SORT_FLAG_CASE)
                        ->values()
                        ->map(fn (CityUtmCampaign $campaign): array => [
                            'name' => $campaign->medium,
                            'phone' => $campaign->phone?->phone,
                        ])
                        ->all(),
                ];
            })
            ->sortBy('source', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    private function mapCampaignStateRows(Collection $campaigns): array
    {
        return $campaigns
            ->values()
            ->map(fn (CityUtmCampaign $campaign): array => [
                'key' => $this->campaignKey($campaign->id),
                'id' => $campaign->id,
                'type' => filled($campaign->medium) ? 'medium' : 'source',
                'source_key' => $this->sourceKey($campaign->source_id),
                'medium' => $campaign->medium,
                'medium_name' => $campaign->medium_name,
                'phone_key' => $campaign->phone_id ? $this->phoneKey($campaign->phone_id) : null,
                'open_booking_widget' => (bool) $campaign->open_booking_widget,
                'started_at' => $campaign->started_at?->format('Y-m-d H:i:s'),
                'stopped_at' => $campaign->stopped_at?->format('Y-m-d H:i:s'),
                'archived_at' => $campaign->archived_at?->format('Y-m-d H:i:s'),
                'restarted_from_id' => $campaign->restarted_from_id,
            ])
            ->all();
    }

    private function buildEditorStateFromTransitionTables(City $city): array
    {
        $city->loadMissing([
            'utmPhones',
            'utmSources.defaultPhone',
            'utmMediums.source',
            'utmMediums.phone',
        ]);

        $state = [
            'phones' => $city->utmPhones
                ->sortBy('phone', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->map(fn (CityUtmPhone $phone): array => [
                    'key' => $this->phoneKey($phone->id),
                    'id' => $phone->id,
                    'phone' => $phone->phone,
                ])
                ->all(),
            'sources' => $city->utmSources
                ->sortBy('source', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->map(fn (CityUtmSource $source): array => [
                    'key' => $this->sourceKey($source->id),
                    'id' => $source->id,
                    'source' => $source->source,
                    'name' => $source->name,
                    'default_phone_key' => $source->default_phone_id ? $this->phoneKey($source->default_phone_id) : null,
                    'open_booking_widget' => (bool) $source->open_booking_widget,
                ])
                ->all(),
            'campaigns' => [],
            'archived_campaigns' => [],
        ];

        foreach ($city->utmSources as $source) {
            if (! $source->default_phone_id && ! $source->open_booking_widget) {
                continue;
            }

            $state['campaigns'][] = [
                'key' => 'campaign-source-transition-' . $source->id,
                'id' => null,
                'type' => 'source',
                'source_key' => $this->sourceKey($source->id),
                'medium' => null,
                'medium_name' => null,
                'phone_key' => $source->default_phone_id ? $this->phoneKey($source->default_phone_id) : null,
                'open_booking_widget' => (bool) $source->open_booking_widget,
                'started_at' => $source->created_at?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s'),
                'stopped_at' => null,
                'archived_at' => null,
                'restarted_from_id' => null,
            ];
        }

        foreach ($city->utmMediums as $medium) {
            $isArchived = ! $this->isTransitionMediumActive($medium);
            $stoppedAt = $medium->end_date?->endOfDay();

            $row = [
                'key' => 'campaign-medium-transition-' . $medium->id,
                'id' => null,
                'type' => 'medium',
                'source_key' => $this->sourceKey($medium->source_id),
                'medium' => $medium->medium,
                'medium_name' => $medium->medium_name,
                'phone_key' => $medium->phone_id ? $this->phoneKey($medium->phone_id) : null,
                'open_booking_widget' => (bool) $medium->open_booking_widget,
                'started_at' => ($medium->start_date?->startOfDay() ?? $medium->created_at ?? now())->format('Y-m-d H:i:s'),
                'stopped_at' => $isArchived ? $stoppedAt?->format('Y-m-d H:i:s') : null,
                'archived_at' => $isArchived ? $stoppedAt?->format('Y-m-d H:i:s') : null,
                'restarted_from_id' => null,
            ];

            if ($isArchived) {
                $state['archived_campaigns'][] = $row;
            } else {
                $state['campaigns'][] = $row;
            }
        }

        $state['campaigns'] = collect($state['campaigns'])
            ->sortBy(fn (array $row): string => $this->campaignStateSortValue($row, collect($state['sources'])->keyBy('key')))
            ->values()
            ->all();

        return $state;
    }

    private function buildEditorStateFromLegacy(array $legacyRules): array
    {
        $phones = [];
        $phonesByNumber = [];
        $sources = [];
        $campaigns = [];

        foreach ($legacyRules as $legacyRule) {
            $sourceValue = trim((string) data_get($legacyRule, 'source', ''));

            if ($sourceValue === '') {
                continue;
            }

            $defaultPhoneKey = $this->legacyPhoneKey(
                trim((string) data_get($legacyRule, 'phone', '')),
                $phones,
                $phonesByNumber,
            );

            $sourceKey = 'source-legacy-' . md5($sourceValue);
            $sources[] = [
                'key' => $sourceKey,
                'id' => null,
                'source' => $sourceValue,
                'name' => '',
                'default_phone_key' => $defaultPhoneKey,
                'open_booking_widget' => false,
            ];

            if ($defaultPhoneKey) {
                $campaigns[] = [
                    'key' => 'campaign-source-legacy-' . md5($sourceValue),
                    'id' => null,
                    'type' => 'source',
                    'source_key' => $sourceKey,
                    'medium' => null,
                    'medium_name' => null,
                    'phone_key' => $defaultPhoneKey,
                    'open_booking_widget' => false,
                    'started_at' => now()->format('Y-m-d H:i:s'),
                    'stopped_at' => null,
                    'archived_at' => null,
                    'restarted_from_id' => null,
                ];
            }

            foreach ((array) data_get($legacyRule, 'medium', []) as $legacyMedium) {
                $mediumValue = trim((string) data_get($legacyMedium, 'name', ''));

                if ($mediumValue === '') {
                    continue;
                }

                $phoneKey = $this->legacyPhoneKey(
                    trim((string) data_get($legacyMedium, 'phone', '')),
                    $phones,
                    $phonesByNumber,
                );

                $campaigns[] = [
                    'key' => 'campaign-medium-legacy-' . md5($sourceValue . '::' . $mediumValue),
                    'id' => null,
                    'type' => 'medium',
                    'source_key' => $sourceKey,
                    'medium' => $mediumValue,
                    'medium_name' => '',
                    'phone_key' => $phoneKey,
                    'open_booking_widget' => false,
                    'started_at' => now()->format('Y-m-d H:i:s'),
                    'stopped_at' => null,
                    'archived_at' => null,
                    'restarted_from_id' => null,
                ];
            }
        }

        return $this->dropConflictingSourceCampaignPhones([
            'phones' => array_values($phones),
            'sources' => $sources,
            'campaigns' => $campaigns,
            'archived_campaigns' => [],
        ]);
    }

    private function syncPhones(City $city, array $phones): array
    {
        $existingPhones = $city->utmPhones()->get()->keyBy('id');
        $keyToId = [];
        $keptIds = [];

        foreach ($phones as $row) {
            $model = $row['id'] ? $existingPhones->get($row['id']) : null;

            if (! $model) {
                $model = new CityUtmPhone();
                $model->city()->associate($city);
            }

            $model->phone = $row['phone'];
            $model->save();

            $keyToId[$row['key']] = $model->id;
            $keptIds[] = $model->id;
        }

        return [
            'key_to_id' => $keyToId,
            'kept_ids' => $keptIds,
        ];
    }

    private function syncSources(City $city, array $sources, array $phoneKeyToId): array
    {
        $existingSources = $city->utmSources()->get()->keyBy('id');
        $keyToId = [];
        $keptIds = [];

        foreach ($sources as $row) {
            $model = $row['id'] ? $existingSources->get($row['id']) : null;

            if (! $model) {
                $model = new CityUtmSource();
                $model->city()->associate($city);
            }

            $model->source = $row['source'];
            $model->name = $row['name'] ?: null;
            $model->default_phone_id = $row['default_phone_key']
                ? ($phoneKeyToId[$row['default_phone_key']] ?? null)
                : null;
            $model->open_booking_widget = (bool) $row['open_booking_widget'];
            $model->save();

            $keyToId[$row['key']] = $model->id;
            $keptIds[] = $model->id;
        }

        return [
            'key_to_id' => $keyToId,
            'kept_ids' => $keptIds,
        ];
    }

    private function syncCampaigns(
        City $city,
        array $activeCampaigns,
        array $archivedCampaigns,
        array $sourceKeyToId,
        array $phoneKeyToId,
    ): array {
        $existingCampaigns = $city->utmCampaigns()->get()->keyBy('id');
        $keptIds = [];

        foreach ($activeCampaigns as $row) {
            $campaignId = $this->storeCampaign($city, $row, false, $existingCampaigns, $sourceKeyToId, $phoneKeyToId);

            if ($campaignId) {
                $keptIds[] = $campaignId;
            }
        }

        foreach ($archivedCampaigns as $row) {
            $campaignId = $this->storeCampaign($city, $row, true, $existingCampaigns, $sourceKeyToId, $phoneKeyToId);

            if ($campaignId) {
                $keptIds[] = $campaignId;
            }
        }

        $query = $city->utmCampaigns();

        if ($keptIds === []) {
            $query->delete();

            return ['kept_ids' => []];
        }

        $query->whereNotIn('id', $keptIds)->delete();

        return ['kept_ids' => $keptIds];
    }

    private function storeCampaign(
        City $city,
        array $row,
        bool $archived,
        Collection $existingCampaigns,
        array $sourceKeyToId,
        array $phoneKeyToId,
    ): ?int {
        $sourceId = $sourceKeyToId[$row['source_key']] ?? null;

        if (! $sourceId) {
            return null;
        }

        $model = $row['id'] ? $existingCampaigns->get($row['id']) : null;

        if (! $model) {
            $model = new CityUtmCampaign();
            $model->city()->associate($city);
        }

        $model->source_id = $sourceId;
        $model->medium = $row['type'] === 'medium' ? $row['medium'] : null;
        $model->medium_name = $row['type'] === 'medium' ? ($row['medium_name'] ?: null) : null;
        $model->phone_id = $row['phone_key'] ? ($phoneKeyToId[$row['phone_key']] ?? null) : null;
        $model->open_booking_widget = (bool) $row['open_booking_widget'];
        $parsedStartedAt = $this->parseEditorDateTime(
            $row['started_at'],
            'У одной из кампаний указана некорректная дата запуска.',
        );

        // Existing campaigns keep their original launch timestamp even when their
        // archived/active state changes in the editor.
        $model->started_at = $model->exists
            ? ($model->started_at ?? $parsedStartedAt ?? CarbonImmutable::now())
            : ($parsedStartedAt ?? CarbonImmutable::now());
        $model->restarted_from_id = $row['restarted_from_id'] ?: null;

        if ($archived) {
            $stoppedAt = $this->parseEditorDateTime(
                $row['stopped_at'] ?: $row['archived_at'],
                'У одной из архивных кампаний указана некорректная дата остановки.',
            ) ?? CarbonImmutable::now();

            $model->stopped_at = $stoppedAt;
            $model->archived_at = $this->parseEditorDateTime(
                $row['archived_at'],
                'У одной из архивных кампаний указана некорректная дата архивации.',
            ) ?? $stoppedAt;
        } else {
            $model->stopped_at = null;
            $model->archived_at = null;
        }

        $model->save();

        return $model->id;
    }

    private function deleteMissingSources(City $city, array $keptIds): void
    {
        $query = $city->utmSources();

        if ($keptIds === []) {
            $query->delete();

            return;
        }

        $query->whereNotIn('id', $keptIds)->delete();
    }

    private function deleteMissingPhones(City $city, array $keptIds): void
    {
        $query = $city->utmPhones();

        if ($keptIds === []) {
            $query->delete();

            return;
        }

        $query->whereNotIn('id', $keptIds)->delete();
    }

    private function validateState(City $city, array $state): void
    {
        $phoneRows = collect($state['phones']);
        $sourceRows = collect($state['sources']);
        $activeCampaignRows = collect($state['campaigns']);
        $archivedCampaignRows = collect($state['archived_campaigns']);
        $allowedLegacyActiveDuplicateUsage = $this->allowedLegacyActiveDuplicateUsage($city);

        if ($phoneRows->pluck('phone')->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages([
                'data.utm_tracker' => 'Один и тот же телефон нельзя добавить в справочник дважды.',
            ]);
        }

        if ($sourceRows->pluck('source')->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages([
                'data.utm_tracker' => 'UTM source должен быть уникальным в пределах города.',
            ]);
        }

        $phoneKeys = $phoneRows->pluck('key')->filter()->all();
        $sourceKeys = $sourceRows->pluck('key')->filter()->all();

        foreach ($sourceRows as $sourceRow) {
            $defaultPhoneKey = $sourceRow['default_phone_key'];

            if ($defaultPhoneKey && ! in_array($defaultPhoneKey, $phoneKeys, true)) {
                throw ValidationException::withMessages([
                    'data.utm_tracker' => 'У одного из source выбран телефон, которого нет в справочнике.',
                ]);
            }
        }

        $this->validateCampaignRows($activeCampaignRows, $sourceKeys, $phoneKeys, false);
        $this->validateCampaignRows($archivedCampaignRows, $sourceKeys, $phoneKeys, true);

        $activeRowsByPhone = $activeCampaignRows
            ->filter(fn (array $row): bool => filled($row['phone_key']))
            ->groupBy('phone_key');

        foreach ($activeRowsByPhone as $phoneKey => $rowsWithPhone) {
            if ($rowsWithPhone->count() <= 1) {
                continue;
            }

            $campaignIds = $rowsWithPhone
                ->pluck('id')
                ->filter()
                ->sort()
                ->values()
                ->all();

            $allowedCampaignIds = $allowedLegacyActiveDuplicateUsage[$phoneKey] ?? null;

            if (
                $allowedCampaignIds === null ||
                count($campaignIds) !== $rowsWithPhone->count() ||
                $campaignIds !== $allowedCampaignIds
            ) {
                throw ValidationException::withMessages([
                    'data.utm_tracker' => 'Телефон нельзя использовать повторно в активных кампаниях.',
                ]);
            }
        }

        if (
            $activeCampaignRows
                ->map(fn (array $row): string => $this->campaignUniqueKey($row))
                ->duplicates()
                ->isNotEmpty()
        ) {
            throw ValidationException::withMessages([
                'data.utm_tracker' => 'Активная UTM-кампания не должна повторяться по source и medium.',
            ]);
        }
    }

    private function validateCampaignRows(
        Collection $rows,
        array $sourceKeys,
        array $phoneKeys,
        bool $archived,
    ): void {
        foreach ($rows as $row) {
            if (! in_array($row['source_key'], $sourceKeys, true)) {
                throw ValidationException::withMessages([
                    'data.utm_tracker' => 'У одной из кампаний не выбран source.',
                ]);
            }

            if ($row['phone_key'] && ! in_array($row['phone_key'], $phoneKeys, true)) {
                throw ValidationException::withMessages([
                    'data.utm_tracker' => 'У одной из кампаний выбран телефон, которого нет в справочнике.',
                ]);
            }

            if ($row['type'] === 'medium' && ! filled($row['medium'])) {
                throw ValidationException::withMessages([
                    'data.utm_tracker' => 'У одной из medium-кампаний не указан utm_medium.',
                ]);
            }

            $startedAt = $this->parseEditorDateTime(
                $row['started_at'],
                'У одной из кампаний указана некорректная дата запуска.',
            );

            $stoppedAt = $this->parseEditorDateTime(
                $row['stopped_at'],
                'У одной из кампаний указана некорректная дата остановки.',
            );

            $archivedAt = $this->parseEditorDateTime(
                $row['archived_at'],
                'У одной из кампаний указана некорректная дата архивации.',
            );

            if ($startedAt && $stoppedAt && $stoppedAt->lt($startedAt)) {
                throw ValidationException::withMessages([
                    'data.utm_tracker' => 'Дата остановки кампании не может быть раньше даты запуска.',
                ]);
            }

            if ($archived && ! $stoppedAt && ! $archivedAt) {
                throw ValidationException::withMessages([
                    'data.utm_tracker' => 'У архивной кампании должна быть дата остановки.',
                ]);
            }
        }
    }

    private function normalizeState(array $state): array
    {
        return [
            'phones' => collect(data_get($state, 'phones', []))
                ->map(fn (mixed $row): array => [
                    'key' => (string) (data_get($row, 'key') ?: 'phone-' . Str::uuid()),
                    'id' => data_get($row, 'id') ? (int) data_get($row, 'id') : null,
                    'phone' => trim((string) data_get($row, 'phone', '')),
                ])
                ->filter(fn (array $row): bool => filled($row['phone']))
                ->values()
                ->all(),
            'sources' => collect(data_get($state, 'sources', []))
                ->map(fn (mixed $row): array => [
                    'key' => (string) (data_get($row, 'key') ?: 'source-' . Str::uuid()),
                    'id' => data_get($row, 'id') ? (int) data_get($row, 'id') : null,
                    'source' => trim((string) data_get($row, 'source', '')),
                    'name' => trim((string) data_get($row, 'name', '')),
                    'default_phone_key' => data_get($row, 'default_phone_key') ? (string) data_get($row, 'default_phone_key') : null,
                    'open_booking_widget' => (bool) data_get($row, 'open_booking_widget', false),
                ])
                ->filter(fn (array $row): bool => filled($row['source']))
                ->values()
                ->all(),
            'campaigns' => $this->normalizeCampaignRows(data_get($state, 'campaigns', [])),
            'archived_campaigns' => $this->normalizeCampaignRows(data_get($state, 'archived_campaigns', [])),
        ];
    }

    private function normalizeCampaignRows(array $rows): array
    {
        return collect($rows)
            ->map(function (mixed $row): array {
                $type = data_get($row, 'type');
                $type = in_array($type, ['source', 'medium'], true)
                    ? $type
                    : (filled(trim((string) data_get($row, 'medium', ''))) ? 'medium' : 'source');

                return $this->normalizeCampaignRowChronology([
                    'key' => (string) (data_get($row, 'key') ?: 'campaign-' . Str::uuid()),
                    'id' => data_get($row, 'id') ? (int) data_get($row, 'id') : null,
                    'type' => $type,
                    'source_key' => data_get($row, 'source_key') ? (string) data_get($row, 'source_key') : null,
                    'medium' => $type === 'medium' ? trim((string) data_get($row, 'medium', '')) : null,
                    'medium_name' => $type === 'medium' ? trim((string) data_get($row, 'medium_name', '')) : null,
                    'phone_key' => data_get($row, 'phone_key') ? (string) data_get($row, 'phone_key') : null,
                    'open_booking_widget' => (bool) data_get($row, 'open_booking_widget', false),
                    'started_at' => $this->normalizeDateTime(data_get($row, 'started_at')),
                    'stopped_at' => $this->normalizeDateTime(data_get($row, 'stopped_at')),
                    'archived_at' => $this->normalizeDateTime(data_get($row, 'archived_at')),
                    'restarted_from_id' => data_get($row, 'restarted_from_id') ? (int) data_get($row, 'restarted_from_id') : null,
                ]);
            })
            ->filter(fn (array $row): bool => filled($row['source_key']) || filled($row['medium']) || filled($row['phone_key']))
            ->values()
            ->all();
    }

    private function legacyPhoneKey(string $phone, array &$phones, array &$phonesByNumber): ?string
    {
        if ($phone === '') {
            return null;
        }

        if (isset($phonesByNumber[$phone])) {
            return $phonesByNumber[$phone];
        }

        $key = 'phone-legacy-' . md5($phone);
        $phonesByNumber[$phone] = $key;
        $phones[$key] = [
            'key' => $key,
            'id' => null,
            'phone' => $phone,
        ];

        return $key;
    }

    private function phoneKey(int $id): string
    {
        return "phone-{$id}";
    }

    private function sourceKey(int $id): string
    {
        return "source-{$id}";
    }

    private function campaignKey(int $id): string
    {
        return "campaign-{$id}";
    }

    private function normalizeDateTime(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $value = str_replace('T', ' ', $value);

        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value)) {
            return $value . ':00';
        }

        return $value;
    }

    private function normalizeCampaignRowChronology(array $row): array
    {
        $startedAt = $this->parseEditorDateTime($row['started_at'], '');
        $stoppedAt = $this->parseEditorDateTime($row['stopped_at'], '');
        $archivedAt = $this->parseEditorDateTime($row['archived_at'], '');

        if ($startedAt && $stoppedAt && $stoppedAt->lt($startedAt)) {
            $row['stopped_at'] = $startedAt->format('Y-m-d H:i:s');
            $stoppedAt = $startedAt;
        }

        if ($startedAt && $archivedAt && $archivedAt->lt($startedAt)) {
            $row['archived_at'] = $startedAt->format('Y-m-d H:i:s');
            $archivedAt = $startedAt;
        }

        if ($stoppedAt && $archivedAt && $archivedAt->lt($stoppedAt)) {
            $row['archived_at'] = $stoppedAt->format('Y-m-d H:i:s');
        }

        return $row;
    }

    private function parseEditorDateTime(?string $value, string $message): ?CarbonImmutable
    {
        if (! $value) {
            return null;
        }

        $formats = [
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d\TH:i:s',
            'Y-m-d\TH:i',
        ];

        foreach ($formats as $format) {
            try {
                $date = CarbonImmutable::createFromFormat($format, $value);

                if ($date) {
                    return $date;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        throw ValidationException::withMessages([
            'data.utm_tracker' => $message,
        ]);
    }

    private function campaignUniqueKey(array $row): string
    {
        if ($row['type'] === 'source') {
            return $row['source_key'] . '::__source__';
        }

        return $row['source_key'] . '::' . $row['medium'];
    }

    private function activeCampaigns(City $city): Collection
    {
        return $city->utmCampaigns
            ->filter(fn (CityUtmCampaign $campaign): bool => $campaign->archived_at === null);
    }

    private function archivedCampaigns(City $city): Collection
    {
        return $city->utmCampaigns
            ->filter(fn (CityUtmCampaign $campaign): bool => $campaign->archived_at !== null);
    }

    private function isTransitionMediumActive(CityUtmMedium $medium): bool
    {
        if (! $medium->end_date) {
            return true;
        }

        return $medium->end_date->endOfDay()->gt(CarbonImmutable::today()->endOfDay());
    }

    private function campaignSortValue(CityUtmCampaign $campaign): string
    {
        $source = strtolower($campaign->source?->source ?? '');
        $type = filled($campaign->medium) ? '1' : '0';
        $medium = strtolower($campaign->medium ?? '');

        return $source . '::' . $type . '::' . $medium;
    }

    private function campaignStateSortValue(array $row, Collection $sourcesByKey): string
    {
        $source = strtolower((string) data_get($sourcesByKey->get($row['source_key']), 'source', ''));
        $type = ($row['type'] ?? 'source') === 'medium' ? '1' : '0';
        $medium = strtolower((string) ($row['medium'] ?? ''));

        return $source . '::' . $type . '::' . $medium;
    }

    private function synchronizeSourceOnlyCampaignRows(array $state): array
    {
        $campaigns = collect(data_get($state, 'campaigns', []))->values();
        $archivedCampaigns = collect(data_get($state, 'archived_campaigns', []))->values();
        $sources = collect(data_get($state, 'sources', []))->values();

        foreach ($sources as $sourceRow) {
            $sourceKey = $sourceRow['key'];
            $defaultPhoneKey = $sourceRow['default_phone_key'] ?? null;

            $activeMediumExists = $campaigns->contains(fn (array $row): bool => ($row['type'] ?? 'source') === 'medium' && ($row['source_key'] ?? null) === $sourceKey);
            $sourceRows = $campaigns->filter(fn (array $row): bool => ($row['type'] ?? 'source') === 'source' && ($row['source_key'] ?? null) === $sourceKey)->values();
            $archivedSourceExists = $archivedCampaigns->contains(fn (array $row): bool => ($row['type'] ?? 'source') === 'source' && ($row['source_key'] ?? null) === $sourceKey);
            $shouldHaveRow = filled($defaultPhoneKey) && ! $activeMediumExists && ($sourceRows->isNotEmpty() || ! $archivedSourceExists);

            if (! $shouldHaveRow) {
                $campaigns = $campaigns->reject(fn (array $row): bool => ($row['type'] ?? 'source') === 'source' && ($row['source_key'] ?? null) === $sourceKey)->values();

                continue;
            }

            if ($sourceRows->isEmpty()) {
                $campaigns->prepend([
                    'key' => 'campaign-source-auto-' . Str::uuid(),
                    'id' => null,
                    'type' => 'source',
                    'source_key' => $sourceKey,
                    'medium' => null,
                    'medium_name' => null,
                    'phone_key' => $defaultPhoneKey,
                    'open_booking_widget' => (bool) ($sourceRow['open_booking_widget'] ?? false),
                    'started_at' => now()->format('Y-m-d H:i:s'),
                    'stopped_at' => null,
                    'archived_at' => null,
                    'restarted_from_id' => null,
                ]);

                continue;
            }

            $primaryRowKey = $sourceRows->first()['key'];

            $campaigns = $campaigns
                ->reject(fn (array $row): bool => ($row['type'] ?? 'source') === 'source' && ($row['source_key'] ?? null) === $sourceKey && ($row['key'] ?? null) !== $primaryRowKey)
                ->map(function (array $row) use ($sourceKey, $defaultPhoneKey, $sourceRow): array {
                    if (($row['type'] ?? 'source') === 'source' && ($row['source_key'] ?? null) === $sourceKey) {
                        $row['phone_key'] = $defaultPhoneKey;
                        $row['open_booking_widget'] = (bool) ($row['open_booking_widget'] ?? $sourceRow['open_booking_widget'] ?? false);
                    }

                    return $row;
                })
                ->values();
        }

        $state['campaigns'] = $this->dropConflictingSourceCampaignPhones([
            'campaigns' => $campaigns->all(),
        ])['campaigns'];

        return $state;
    }

    private function dropConflictingSourceCampaignPhones(array $state): array
    {
        $mediumPhoneKeys = collect(data_get($state, 'campaigns', []))
            ->filter(fn (array $row): bool => ($row['type'] ?? 'source') === 'medium')
            ->pluck('phone_key')
            ->filter()
            ->unique()
            ->all();

        if ($mediumPhoneKeys === []) {
            return $state;
        }

        $state['campaigns'] = collect(data_get($state, 'campaigns', []))
            ->map(function (array $row) use ($mediumPhoneKeys): array {
                if (($row['type'] ?? 'source') === 'source' && in_array($row['phone_key'] ?? null, $mediumPhoneKeys, true)) {
                    $row['phone_key'] = null;
                }

                return $row;
            })
            ->all();

        return $state;
    }

    private function allowedLegacyActiveDuplicateUsage(City $city): array
    {
        return $city->utmCampaigns()
            ->whereNull('archived_at')
            ->get(['id', 'phone_id'])
            ->filter(fn (CityUtmCampaign $campaign): bool => filled($campaign->phone_id))
            ->groupBy('phone_id')
            ->filter(fn (Collection $rows): bool => $rows->count() > 1)
            ->mapWithKeys(function (Collection $rows, int|string $phoneId): array {
                return [
                    $this->phoneKey((int) $phoneId) => $rows
                        ->pluck('id')
                        ->sort()
                        ->values()
                        ->all(),
                ];
            })
            ->all();
    }
}
