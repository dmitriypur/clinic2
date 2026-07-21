<?php

namespace App\Services;

use App\Models\SiteSearchQuery;

class SiteSearchAnalyticsRecorder
{
    public function record(string $query, ?int $cityId, int $resultsCount): void
    {
        $query = $this->normalize($query);

        if ($query === '' || $this->containsPersonalData($query)) {
            return;
        }

        SiteSearchQuery::create([
            'query' => mb_substr($query, 0, 100, 'UTF-8'),
            'city_id' => $cityId,
            'results_count' => max(0, $resultsCount),
        ]);
    }

    private function normalize(string $query): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $query));
    }

    private function containsPersonalData(string $query): bool
    {
        if (preg_match('/[^\s@]+@[^\s@]+/u', $query) === 1) {
            return true;
        }

        return preg_match('/[A-Z0-9.!#$%&\'*+\/=?^_`{|}~-]+@[A-Z0-9](?:[A-Z0-9-]{0,61}[A-Z0-9])?(?:\.[A-Z0-9](?:[A-Z0-9-]{0,61}[A-Z0-9])?)+/iu', $query) === 1
        || preg_match('/\+?\d(?:[\s().-]*\d){6,}/u', $query) === 1;
    }
}
