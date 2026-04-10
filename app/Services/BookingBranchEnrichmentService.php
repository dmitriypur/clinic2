<?php

namespace App\Services;

use App\Models\City;

class BookingBranchEnrichmentService
{
    private const OVERRIDE_FIELDS = [
        'address',
        'metro',
        'price',
    ];

    public function enrichPayload(array $payload, ?City $city): array
    {
        if (! $city) {
            return $payload;
        }

        if (array_is_list($payload)) {
            return $this->enrichBranches($payload, $city);
        }

        $data = data_get($payload, 'data');

        if (! is_array($data)) {
            return $payload;
        }

        $payload['data'] = $this->enrichBranches($data, $city);

        return $payload;
    }

    public function enrichBranches(array $branches, ?City $city): array
    {
        if (! $city || empty($branches)) {
            return $branches;
        }

        $localBranches = $this->buildLocalBranches($city);

        if ($localBranches === []) {
            return $branches;
        }

        return array_map(function (mixed $branch) use ($localBranches, $city): mixed {
            if (! is_array($branch)) {
                return $branch;
            }

            if (blank(data_get($branch, 'city')) && filled($city->name)) {
                $branch['city'] = $city->name;
            }

            $localBranch = $this->findMatchingLocalBranch($branch, $localBranches);

            if (! $localBranch) {
                return $branch;
            }

            $overrides = $this->extractOverrides($localBranch);

            foreach (self::OVERRIDE_FIELDS as $field) {
                $value = $overrides[$field] ?? null;

                if (filled($value)) {
                    $branch[$field] = $value;
                }
            }

            return $branch;
        }, $branches);
    }

    public function buildOverridesByExternalId(?City $city): array
    {
        $overrides = [];

        foreach ($this->buildLocalBranches($city) as $branch) {
            $externalId = $this->normalizeExternalId(data_get($branch, 'external_id'));

            if (! $externalId) {
                continue;
            }

            $overrides[$externalId] = $this->extractOverrides($branch);
        }

        return $overrides;
    }

    private function buildLocalBranches(?City $city): array
    {
        if (! $city) {
            return [];
        }

        return collect(is_array($city->branches) ? $city->branches : [])
            ->filter(fn (mixed $branch): bool => is_array($branch))
            ->values()
            ->all();
    }

    private function extractOverrides(array $branch): array
    {
        $overrides = [];

        foreach (self::OVERRIDE_FIELDS as $field) {
            $value = data_get($branch, $field);

            if (is_string($value)) {
                $value = trim($value);
            }

            $overrides[$field] = $value;
        }

        return $overrides;
    }

    private function findMatchingLocalBranch(array $apiBranch, array $localBranches): ?array
    {
        $apiExternalId = $this->normalizeExternalId(data_get($apiBranch, 'external_id'));

        if ($apiExternalId) {
            foreach ($localBranches as $localBranch) {
                if ($this->normalizeExternalId(data_get($localBranch, 'external_id')) === $apiExternalId) {
                    return $localBranch;
                }
            }
        }

        $apiAddress = $this->normalizeComparableText(data_get($apiBranch, 'address'));
        $apiName = $this->normalizeComparableText(data_get($apiBranch, 'name', data_get($apiBranch, 'title')));
        $apiPhone = $this->normalizePhone(data_get($apiBranch, 'phone'));
        $apiEmail = $this->normalizeComparableText(data_get($apiBranch, 'email'));
        $apiCoordinates = $this->normalizeComparableText(data_get($apiBranch, 'coordinates'));

        if ($apiAddress) {
            foreach ($localBranches as $localBranch) {
                $localAddress = $this->normalizeComparableText(data_get($localBranch, 'address'));

                if ($this->textsMatch($apiAddress, $localAddress)) {
                    return $localBranch;
                }
            }
        }

        if ($apiName) {
            foreach ($localBranches as $localBranch) {
                $localName = $this->normalizeComparableText(data_get($localBranch, 'name', data_get($localBranch, 'title')));

                if ($this->textsMatch($apiName, $localName)) {
                    return $localBranch;
                }
            }
        }

        if ($apiPhone) {
            foreach ($localBranches as $localBranch) {
                $localPhone = $this->normalizePhone(data_get($localBranch, 'phone'));

                if ($localPhone && $apiPhone === $localPhone) {
                    return $localBranch;
                }
            }
        }

        if ($apiEmail) {
            foreach ($localBranches as $localBranch) {
                $localEmail = $this->normalizeComparableText(data_get($localBranch, 'email'));

                if ($localEmail && $apiEmail === $localEmail) {
                    return $localBranch;
                }
            }
        }

        if ($apiCoordinates) {
            foreach ($localBranches as $localBranch) {
                $localCoordinates = $this->normalizeComparableText(data_get($localBranch, 'coordinates'));

                if ($localCoordinates && $apiCoordinates === $localCoordinates) {
                    return $localBranch;
                }
            }
        }

        return null;
    }

    private function normalizeExternalId(mixed $externalId): ?string
    {
        if ($externalId === null) {
            return null;
        }

        $normalized = mb_strtolower(trim((string) $externalId));

        return $normalized !== '' ? $normalized : null;
    }

    private function normalizeComparableText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = mb_strtolower(trim((string) $value));

        if ($normalized === '') {
            return null;
        }

        $normalized = str_replace(
            [
                'ул.',
                'улица',
                'д.',
                'дом',
                'ш.',
                'шоссе',
                'пр-кт',
                'просп.',
                'проспект',
                'пл.',
                'площадь',
                'переулок',
                'пер.',
                'город',
                'г.',
            ],
            [
                ' ',
                ' ',
                ' ',
                ' ',
                ' шоссе ',
                ' шоссе ',
                ' проспект ',
                ' проспект ',
                ' проспект ',
                ' площадь ',
                ' площадь ',
                ' ',
                ' ',
                ' ',
                ' ',
            ],
            $normalized
        );
        $normalized = preg_replace('/[^[:alnum:][:space:]]/u', ' ', (string) $normalized);
        $normalized = preg_replace('/\s+/u', ' ', (string) $normalized);
        $normalized = trim((string) $normalized);

        return $normalized !== '' ? $normalized : null;
    }

    private function normalizePhone(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = preg_replace('/\D+/', '', (string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    private function textsMatch(?string $left, ?string $right): bool
    {
        if (! $left || ! $right) {
            return false;
        }

        return $left === $right
            || str_contains($left, $right)
            || str_contains($right, $left);
    }
}
