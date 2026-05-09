<?php

namespace App\Services;

use App\Models\City;
use App\Models\CityUtmCampaign;
use App\Models\CityUtmPhone;
use App\Models\CityUtmSource;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class UtmTrackerIntegrationExportService
{
    public function export(): array
    {
        return [
            'meta' => [
                'schema_version' => 1,
                'generated_at' => now()->toISOString(),
            ],
            'cities' => City::query()
                ->where('active', true)
                ->with([
                    'utmPhones',
                    'utmSources.defaultPhone',
                    'utmCampaigns.source',
                    'utmCampaigns.phone',
                ])
                ->orderBy('name')
                ->orderBy('slug')
                ->get()
                ->map(fn (City $city): array => $this->mapCity($city))
                ->values()
                ->all(),
        ];
    }

    private function mapCity(City $city): array
    {
        return [
            'id' => $city->id,
            'slug' => $city->slug,
            'name' => $city->name,
            'phones' => $city->utmPhones
                ->sortBy('phone', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->map(fn (CityUtmPhone $phone): array => $this->mapPhone($phone))
                ->all(),
            'sources' => $city->utmSources
                ->sortBy('source', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->map(fn (CityUtmSource $source): array => $this->mapSource($city, $source))
                ->all(),
        ];
    }

    private function mapSource(City $city, CityUtmSource $source): array
    {
        $rules = $city->utmCampaigns
            ->filter(fn (CityUtmCampaign $campaign): bool => (int) $campaign->source_id === (int) $source->id);

        return [
            'id' => $source->id,
            'utm_source' => $source->source,
            'name' => $source->name,
            'is_organic_default' => (bool) $source->is_organic,
            'default_phone' => $source->defaultPhone ? $this->mapPhone($source->defaultPhone) : null,
            'active_rules' => $this->mapRules(
                $rules->filter(fn (CityUtmCampaign $campaign): bool => $campaign->archived_at === null),
                $source,
                'active'
            ),
            'archived_rules' => $this->mapRules(
                $rules->filter(fn (CityUtmCampaign $campaign): bool => $campaign->archived_at !== null),
                $source,
                'archived'
            ),
        ];
    }

    private function mapRules(Collection $rules, CityUtmSource $source, string $status): array
    {
        return $rules
            ->sort(fn (CityUtmCampaign $left, CityUtmCampaign $right): int => $this->compareRules($left, $right))
            ->values()
            ->map(fn (CityUtmCampaign $rule): array => $this->mapRule($rule, $source, $status))
            ->all();
    }

    private function mapRule(CityUtmCampaign $rule, CityUtmSource $source, string $status): array
    {
        return [
            'id' => $rule->id,
            'type' => $this->ruleType($rule),
            'priority' => $this->rulePriority($rule),
            'utm' => [
                'source' => $source->source,
                'medium' => $rule->medium,
                'campaign' => $rule->campaign,
            ],
            'labels' => [
                'source_name' => $source->name,
                'medium_name' => $rule->medium_name,
                'campaign_name' => $rule->campaign_name,
            ],
            'phone' => $rule->phone ? $this->mapPhone($rule->phone) : null,
            'open_booking_widget' => (bool) $rule->open_booking_widget,
            'organic' => [
                'effective' => $this->effectiveOrganic($rule, $source),
                'source_default' => (bool) $source->is_organic,
                'overridden' => (bool) $rule->is_organic_overridden,
                'override_value' => $rule->is_organic_overridden ? (bool) $rule->is_organic : null,
            ],
            'status' => $status,
            'started_at' => $this->formatDate($rule->started_at),
            'stopped_at' => $this->formatDate($rule->stopped_at),
            'archived_at' => $this->formatDate($rule->archived_at),
            'created_at' => $this->formatDate($rule->created_at),
            'updated_at' => $this->formatDate($rule->updated_at),
        ];
    }

    private function mapPhone(CityUtmPhone $phone): array
    {
        return [
            'id' => $phone->id,
            'phone' => $phone->phone,
        ];
    }

    private function ruleType(CityUtmCampaign $rule): string
    {
        if (filled($rule->campaign)) {
            return 'campaign';
        }

        if (filled($rule->medium)) {
            return 'medium';
        }

        return 'source';
    }

    private function rulePriority(CityUtmCampaign $rule): int
    {
        return match ($this->ruleType($rule)) {
            'campaign' => 3,
            'medium' => 2,
            default => 1,
        };
    }

    private function compareRules(CityUtmCampaign $left, CityUtmCampaign $right): int
    {
        return ($this->rulePriority($left) <=> $this->rulePriority($right))
            ?: strnatcasecmp((string) $left->medium, (string) $right->medium)
            ?: strnatcasecmp((string) $left->campaign, (string) $right->campaign)
            ?: (($left->started_at?->timestamp ?? 0) <=> ($right->started_at?->timestamp ?? 0));
    }

    private function effectiveOrganic(CityUtmCampaign $rule, CityUtmSource $source): bool
    {
        if ($rule->is_organic_overridden) {
            return (bool) $rule->is_organic;
        }

        return (bool) $source->is_organic;
    }

    private function formatDate(?CarbonInterface $date): ?string
    {
        return $date?->toISOString();
    }
}
