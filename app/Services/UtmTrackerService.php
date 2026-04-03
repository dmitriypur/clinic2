<?php

namespace App\Services;

use App\Models\City;
use App\Models\CityUtmMedium;
use App\Models\CityUtmPhone;
use App\Models\CityUtmSource;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UtmTrackerService
{
    public function emptyEditorState(): array
    {
        return [
            'phones' => [],
            'sources' => [],
            'mediums' => [],
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
            'utmMediums.source',
            'utmMediums.phone',
        ]);

        if (
            $city->utmPhones->isEmpty() &&
            $city->utmSources->isEmpty() &&
            $city->utmMediums->isEmpty()
        ) {
            return $this->prioritizeMediumPhones($this->buildEditorStateFromLegacy($city->utm_phones ?? []));
        }

        return $this->prioritizeMediumPhones([
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
            'mediums' => $city->utmMediums
                ->sortBy([
                    fn (CityUtmMedium $medium) => strtolower($medium->source?->source ?? ''),
                    fn (CityUtmMedium $medium) => strtolower($medium->medium),
                ])
                ->values()
                ->map(fn (CityUtmMedium $medium): array => [
                    'key' => $this->mediumKey($medium->id),
                    'id' => $medium->id,
                    'source_key' => $this->sourceKey($medium->source_id),
                    'medium' => $medium->medium,
                    'medium_name' => $medium->medium_name,
                    'phone_key' => $medium->phone_id ? $this->phoneKey($medium->phone_id) : null,
                    'open_booking_widget' => (bool) $medium->open_booking_widget,
                    'start_date' => $medium->start_date?->format('Y-m-d'),
                    'end_date' => $medium->end_date?->format('Y-m-d'),
                ])
                ->all(),
        ]);
    }

    public function sync(City $city, array $state): void
    {
        $state = $this->prioritizeMediumPhones($this->normalizeState($state));
        $this->validateState($city, $state);

        $phoneSync = $this->syncPhones($city, $state['phones']);
        $sourceSync = $this->syncSources($city, $state['sources'], $phoneSync['key_to_id']);
        $this->syncMediums($city, $state['mediums'], $sourceSync['key_to_id'], $phoneSync['key_to_id']);

        $this->deleteMissingSources($city, $sourceSync['kept_ids']);
        $this->deleteMissingPhones($city, $phoneSync['kept_ids']);

        $city->update([
            'utm_phones' => $this->buildLegacyRules(
                $city->fresh(['utmSources.defaultPhone', 'utmSources.mediums.phone'])
            ),
        ]);
    }

    public function buildLegacyRules(City $city): array
    {
        $city->loadMissing(['utmSources.defaultPhone', 'utmSources.mediums.phone']);

        return $city->utmSources
            ->sortBy('source', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->map(function (CityUtmSource $source): array {
                return [
                    'source' => $source->source,
                    'phone' => $source->defaultPhone?->phone,
                    'medium' => $source->mediums
                        ->sortBy('medium', SORT_NATURAL | SORT_FLAG_CASE)
                        ->filter(fn (CityUtmMedium $medium): bool => $this->isMediumActive($medium))
                        ->values()
                        ->map(fn (CityUtmMedium $medium): array => [
                            'name' => $medium->medium,
                            'phone' => $medium->phone?->phone,
                        ])
                        ->all(),
                ];
            })
            ->all();
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

    private function syncMediums(City $city, array $mediums, array $sourceKeyToId, array $phoneKeyToId): void
    {
        $existingMediums = $city->utmMediums()->get()->keyBy('id');
        $keptIds = [];

        foreach ($mediums as $row) {
            $sourceId = $sourceKeyToId[$row['source_key']] ?? null;
            $phoneId = $phoneKeyToId[$row['phone_key']] ?? null;

            if (! $sourceId || ! $phoneId) {
                continue;
            }

            $model = $row['id'] ? $existingMediums->get($row['id']) : null;

            if (! $model) {
                $model = new CityUtmMedium();
                $model->city()->associate($city);
            }

            $model->source_id = $sourceId;
            $model->medium = $row['medium'];
            $model->medium_name = $row['medium_name'] ?: null;
            $model->phone_id = $phoneId;
            $model->open_booking_widget = (bool) $row['open_booking_widget'];
            $model->start_date = $row['start_date'];
            $model->end_date = $row['end_date'];
            $model->save();

            $keptIds[] = $model->id;
        }

        $query = $city->utmMediums();

        if ($keptIds === []) {
            $query->delete();

            return;
        }

        $query->whereNotIn('id', $keptIds)->delete();
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
        $mediumRows = collect($state['mediums']);
        $allowedLegacyMediumDuplicates = $this->allowedLegacyMediumDuplicateUsage($city);

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
        $usedPhoneKeys = [];

        foreach ($sourceRows as $sourceRow) {
            $defaultPhoneKey = $sourceRow['default_phone_key'];

            if ($defaultPhoneKey && ! in_array($defaultPhoneKey, $phoneKeys, true)) {
                throw ValidationException::withMessages([
                    'data.utm_tracker' => 'У одного из source выбран телефон, которого нет в справочнике.',
                ]);
            }

            if ($defaultPhoneKey && in_array($defaultPhoneKey, $usedPhoneKeys, true)) {
                throw ValidationException::withMessages([
                    'data.utm_tracker' => 'Телефон нельзя использовать повторно в разных source и medium.',
                ]);
            }

            if ($defaultPhoneKey) {
                $usedPhoneKeys[] = $defaultPhoneKey;
            }
        }

        $mediumKeysPerSource = [];
        $mediumRowsByPhone = [];

        foreach ($mediumRows as $mediumRow) {
            if (! in_array($mediumRow['source_key'], $sourceKeys, true)) {
                throw ValidationException::withMessages([
                    'data.utm_tracker' => 'У одного из medium не выбран родительский source.',
                ]);
            }

            if (! filled($mediumRow['medium'])) {
                throw ValidationException::withMessages([
                    'data.utm_tracker' => 'У одного из medium не указан utm_medium.',
                ]);
            }

            if (! in_array($mediumRow['phone_key'], $phoneKeys, true)) {
                throw ValidationException::withMessages([
                    'data.utm_tracker' => 'У одного из medium не выбран телефон из справочника.',
                ]);
            }

            $startDate = $this->parseEditorDate(
                $mediumRow['start_date'],
                'У одного из medium указана некорректная дата начала.',
            );
            $endDate = $this->parseEditorDate(
                $mediumRow['end_date'],
                'У одного из medium указана некорректная дата окончания.',
            );

            if ($startDate && $endDate && $endDate->lt($startDate)) {
                throw ValidationException::withMessages([
                    'data.utm_tracker' => 'Дата окончания medium не может быть раньше даты начала.',
                ]);
            }

            $sourceMediumComposite = $mediumRow['source_key'] . '::' . $mediumRow['medium'];
            if (in_array($sourceMediumComposite, $mediumKeysPerSource, true)) {
                throw ValidationException::withMessages([
                    'data.utm_tracker' => 'Одинаковый utm_medium не должен повторяться у одного и того же source.',
                ]);
            }

            $mediumKeysPerSource[] = $sourceMediumComposite;
            $mediumRowsByPhone[$mediumRow['phone_key']][] = $mediumRow;
        }

        foreach ($sourceRows as $sourceRow) {
            $defaultPhoneKey = $sourceRow['default_phone_key'];

            if (! $defaultPhoneKey) {
                continue;
            }

            $rowsWithSamePhone = $mediumRowsByPhone[$defaultPhoneKey] ?? [];

            if ($rowsWithSamePhone === []) {
                continue;
            }

            $hasForeignMediumUsage = collect($rowsWithSamePhone)
                ->contains(fn (array $mediumRow): bool => $mediumRow['source_key'] !== $sourceRow['key']);

            if ($hasForeignMediumUsage) {
                throw ValidationException::withMessages([
                    'data.utm_tracker' => 'Source может использовать как дефолтный только свой телефон или телефон одного из собственных medium.',
                ]);
            }
        }

        foreach ($mediumRowsByPhone as $phoneKey => $rowsWithPhone) {
            if (count($rowsWithPhone) <= 1) {
                continue;
            }

            $mediumIds = collect($rowsWithPhone)
                ->pluck('id')
                ->filter()
                ->sort()
                ->values()
                ->all();

            $allowedMediumIds = $allowedLegacyMediumDuplicates[$phoneKey] ?? null;

            if (
                $allowedMediumIds === null ||
                count($mediumIds) !== count($rowsWithPhone) ||
                $mediumIds !== $allowedMediumIds
            ) {
                throw ValidationException::withMessages([
                    'data.utm_tracker' => 'Телефон нельзя использовать повторно в разных source и medium.',
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
            'mediums' => collect(data_get($state, 'mediums', []))
                ->map(fn (mixed $row): array => [
                    'key' => (string) (data_get($row, 'key') ?: 'medium-' . Str::uuid()),
                    'id' => data_get($row, 'id') ? (int) data_get($row, 'id') : null,
                    'source_key' => data_get($row, 'source_key') ? (string) data_get($row, 'source_key') : null,
                    'medium' => trim((string) data_get($row, 'medium', '')),
                    'medium_name' => trim((string) data_get($row, 'medium_name', '')),
                    'phone_key' => data_get($row, 'phone_key') ? (string) data_get($row, 'phone_key') : null,
                    'open_booking_widget' => (bool) data_get($row, 'open_booking_widget', false),
                    'start_date' => $this->normalizeDate(data_get($row, 'start_date')),
                    'end_date' => $this->normalizeDate(data_get($row, 'end_date')),
                ])
                ->filter(fn (array $row): bool => filled($row['source_key']) || filled($row['medium']) || filled($row['phone_key']))
                ->values()
                ->all(),
        ];
    }

    private function buildEditorStateFromLegacy(array $legacyRules): array
    {
        $phones = [];
        $phonesByNumber = [];
        $sources = [];
        $mediums = [];

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

                $mediums[] = [
                    'key' => 'medium-legacy-' . md5($sourceValue . '::' . $mediumValue),
                    'id' => null,
                    'source_key' => $sourceKey,
                    'medium' => $mediumValue,
                    'medium_name' => '',
                    'phone_key' => $phoneKey,
                    'open_booking_widget' => false,
                    'start_date' => null,
                    'end_date' => null,
                ];
            }
        }

        return [
            'phones' => array_values($phones),
            'sources' => $sources,
            'mediums' => $mediums,
        ];
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

    private function mediumKey(int $id): string
    {
        return "medium-{$id}";
    }

    private function normalizeDate(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function parseEditorDate(?string $value, string $message): ?CarbonImmutable
    {
        if (! $value) {
            return null;
        }

        try {
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                throw new \InvalidArgumentException('Invalid date format.');
            }

            $date = CarbonImmutable::createFromFormat('Y-m-d', $value);

            if (! $date || $date->format('Y-m-d') !== $value) {
                throw new \InvalidArgumentException('Invalid date value.');
            }

            return $date->startOfDay();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'data.utm_tracker' => $message,
            ]);
        }
    }

    private function prioritizeMediumPhones(array $state): array
    {
        $mediumPhoneSources = collect(data_get($state, 'mediums', []))
            ->filter(fn (array $row): bool => filled($row['phone_key'] ?? null) && filled($row['source_key'] ?? null))
            ->groupBy('phone_key')
            ->map(fn ($rows): array => $rows->pluck('source_key')->filter()->unique()->values()->all())
            ->all();

        if ($mediumPhoneSources === []) {
            return $state;
        }

        $state['sources'] = collect(data_get($state, 'sources', []))
            ->map(function (array $sourceRow) use ($mediumPhoneSources): array {
                $defaultPhoneKey = $sourceRow['default_phone_key'] ?? null;

                if (
                    filled($defaultPhoneKey) &&
                    isset($mediumPhoneSources[$defaultPhoneKey]) &&
                    collect($mediumPhoneSources[$defaultPhoneKey])
                        ->contains(fn (string $sourceKey): bool => $sourceKey !== $sourceRow['key'])
                ) {
                    $sourceRow['default_phone_key'] = null;
                }

                return $sourceRow;
            })
            ->all();

        return $state;
    }

    private function allowedLegacyMediumDuplicateUsage(City $city): array
    {
        return $city->utmMediums()
            ->get(['id', 'phone_id'])
            ->filter(fn (CityUtmMedium $medium): bool => filled($medium->phone_id))
            ->groupBy('phone_id')
            ->filter(fn ($rows): bool => $rows->count() > 1)
            ->mapWithKeys(function ($rows, $phoneId): array {
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

    private function isMediumActive(CityUtmMedium $medium): bool
    {
        $today = CarbonImmutable::today();
        $startDate = $medium->start_date?->startOfDay();
        $endDate = $medium->end_date?->startOfDay();

        if ($startDate && $startDate->gt($today)) {
            return false;
        }

        if ($endDate && $endDate->lte($today)) {
            return false;
        }

        return true;
    }
}
