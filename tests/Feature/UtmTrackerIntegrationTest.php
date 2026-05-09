<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\CityUtmCampaign;
use App\Models\CityUtmPhone;
use App\Models\CityUtmSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UtmTrackerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_endpoint_exports_structured_utm_tracker_data_for_active_cities(): void
    {
        $activeCity = City::create([
            'name' => 'Киров',
            'slug' => 'kirov',
            'is_default' => true,
            'active' => true,
        ]);

        $inactiveCity = City::create([
            'name' => 'Москва',
            'slug' => 'moskva',
            'is_default' => false,
            'active' => false,
        ]);

        $sourcePhone = CityUtmPhone::create([
            'city_id' => $activeCity->id,
            'phone' => '+7 000 000-00-01',
        ]);
        $mediumPhone = CityUtmPhone::create([
            'city_id' => $activeCity->id,
            'phone' => '+7 000 000-00-02',
        ]);
        $campaignPhone = CityUtmPhone::create([
            'city_id' => $activeCity->id,
            'phone' => '+7 000 000-00-03',
        ]);
        $archivedPhone = CityUtmPhone::create([
            'city_id' => $activeCity->id,
            'phone' => '+7 000 000-00-04',
        ]);

        $source = CityUtmSource::create([
            'city_id' => $activeCity->id,
            'source' => 'vk',
            'name' => 'VK Ads',
            'default_phone_id' => $sourcePhone->id,
            'open_booking_widget' => false,
            'is_organic' => true,
        ]);

        CityUtmSource::create([
            'city_id' => $inactiveCity->id,
            'source' => 'hidden',
            'name' => 'Hidden',
            'default_phone_id' => null,
            'open_booking_widget' => false,
            'is_organic' => false,
        ]);

        CityUtmCampaign::create([
            'city_id' => $activeCity->id,
            'source_id' => $source->id,
            'medium' => null,
            'medium_name' => null,
            'campaign' => null,
            'campaign_name' => null,
            'phone_id' => $sourcePhone->id,
            'open_booking_widget' => false,
            'is_organic' => false,
            'is_organic_overridden' => false,
            'started_at' => '2026-05-01 09:00:00',
        ]);

        CityUtmCampaign::create([
            'city_id' => $activeCity->id,
            'source_id' => $source->id,
            'medium' => 'banner',
            'medium_name' => 'Banner',
            'campaign' => null,
            'campaign_name' => null,
            'phone_id' => $mediumPhone->id,
            'open_booking_widget' => true,
            'is_organic' => false,
            'is_organic_overridden' => true,
            'started_at' => '2026-05-02 09:00:00',
        ]);

        CityUtmCampaign::create([
            'city_id' => $activeCity->id,
            'source_id' => $source->id,
            'medium' => 'banner',
            'medium_name' => 'Banner',
            'campaign' => 'test',
            'campaign_name' => 'Test Campaign',
            'phone_id' => $campaignPhone->id,
            'open_booking_widget' => false,
            'is_organic' => false,
            'is_organic_overridden' => false,
            'started_at' => '2026-05-03 09:00:00',
        ]);

        CityUtmCampaign::create([
            'city_id' => $activeCity->id,
            'source_id' => $source->id,
            'medium' => 'old',
            'medium_name' => 'Old',
            'campaign' => null,
            'campaign_name' => null,
            'phone_id' => $archivedPhone->id,
            'open_booking_widget' => false,
            'is_organic' => false,
            'is_organic_overridden' => false,
            'started_at' => '2026-04-01 09:00:00',
            'stopped_at' => '2026-04-10 09:00:00',
            'archived_at' => '2026-04-10 09:00:00',
        ]);

        $this->getJson('/api/integrations/utm-tracker')
            ->assertOk()
            ->assertJsonPath('meta.schema_version', 1)
            ->assertJsonCount(1, 'cities')
            ->assertJsonPath('cities.0.slug', 'kirov')
            ->assertJsonPath('cities.0.sources.0.utm_source', 'vk')
            ->assertJsonPath('cities.0.sources.0.is_organic_default', true)
            ->assertJsonPath('cities.0.sources.0.default_phone.phone', '+7 000 000-00-01')
            ->assertJsonCount(3, 'cities.0.sources.0.active_rules')
            ->assertJsonCount(1, 'cities.0.sources.0.archived_rules')
            ->assertJsonPath('cities.0.sources.0.active_rules.0.type', 'source')
            ->assertJsonPath('cities.0.sources.0.active_rules.0.priority', 1)
            ->assertJsonPath('cities.0.sources.0.active_rules.0.organic.effective', true)
            ->assertJsonPath('cities.0.sources.0.active_rules.0.organic.overridden', false)
            ->assertJsonPath('cities.0.sources.0.active_rules.0.organic.override_value', null)
            ->assertJsonPath('cities.0.sources.0.active_rules.1.type', 'medium')
            ->assertJsonPath('cities.0.sources.0.active_rules.1.utm.medium', 'banner')
            ->assertJsonPath('cities.0.sources.0.active_rules.1.phone.phone', '+7 000 000-00-02')
            ->assertJsonPath('cities.0.sources.0.active_rules.1.open_booking_widget', true)
            ->assertJsonPath('cities.0.sources.0.active_rules.1.organic.effective', false)
            ->assertJsonPath('cities.0.sources.0.active_rules.1.organic.overridden', true)
            ->assertJsonPath('cities.0.sources.0.active_rules.1.organic.override_value', false)
            ->assertJsonPath('cities.0.sources.0.active_rules.2.type', 'campaign')
            ->assertJsonPath('cities.0.sources.0.active_rules.2.priority', 3)
            ->assertJsonPath('cities.0.sources.0.active_rules.2.utm.campaign', 'test')
            ->assertJsonPath('cities.0.sources.0.active_rules.2.labels.campaign_name', 'Test Campaign')
            ->assertJsonPath('cities.0.sources.0.active_rules.2.phone.phone', '+7 000 000-00-03')
            ->assertJsonPath('cities.0.sources.0.archived_rules.0.status', 'archived')
            ->assertJsonPath('cities.0.sources.0.archived_rules.0.utm.medium', 'old')
            ->assertJsonMissing(['slug' => 'moskva'])
            ->assertJsonMissingPath('cities.0.utm_phones');
    }
}
