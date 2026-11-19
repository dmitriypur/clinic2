<?php

namespace Tests\Unit\Services;

use App\Models\City;
use App\Models\CityUtmCampaign;
use App\Models\CityUtmMedium;
use App\Models\CityUtmPhone;
use App\Models\CityUtmSource;
use App\Services\UtmTrackerService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UtmTrackerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasColumn('cities', 'utm_phones')) {
            Schema::table('cities', function (Blueprint $table): void {
                $table->json('utm_phones')->nullable();
            });
        }
    }

    /** @test */
    public function it_keeps_only_active_campaigns_in_legacy_city_json(): void
    {
        $city = City::query()->create([
            'name' => 'Киров',
            'slug' => 'kirov',
            'active' => true,
        ]);

        app(UtmTrackerService::class)->sync($city, [
            'phones' => [
                ['key' => 'phone-template', 'phone' => '+7 000 000-00-01'],
                ['key' => 'phone-source', 'phone' => '+7 000 000-00-02'],
                ['key' => 'phone-active', 'phone' => '+7 000 000-00-03'],
                ['key' => 'phone-archive', 'phone' => '+7 000 000-00-04'],
            ],
            'sources' => [
                [
                    'key' => 'source-google',
                    'source' => 'google',
                    'name' => 'Google Ads',
                    'default_phone_key' => 'phone-template',
                ],
            ],
            'campaigns' => [
                [
                    'key' => 'campaign-source',
                    'type' => 'source',
                    'source_key' => 'source-google',
                    'phone_key' => 'phone-source',
                    'open_booking_widget' => true,
                    'started_at' => now()->subDay()->format('Y-m-d H:i:s'),
                ],
                [
                    'key' => 'campaign-medium-active',
                    'type' => 'medium',
                    'source_key' => 'source-google',
                    'medium' => 'cpc',
                    'medium_name' => 'Google CPC',
                    'phone_key' => 'phone-active',
                    'started_at' => now()->subHours(2)->format('Y-m-d H:i:s'),
                ],
            ],
            'archived_campaigns' => [
                [
                    'key' => 'campaign-medium-archive',
                    'type' => 'medium',
                    'source_key' => 'source-google',
                    'medium' => 'pause',
                    'medium_name' => 'Google Pause',
                    'phone_key' => 'phone-archive',
                    'started_at' => now()->subDays(5)->format('Y-m-d H:i:s'),
                    'stopped_at' => now()->subDay()->format('Y-m-d H:i:s'),
                    'archived_at' => now()->subDay()->format('Y-m-d H:i:s'),
                ],
            ],
        ]);

        $legacyRules = $city->fresh()->utm_phones;

        $this->assertSame('google', data_get($legacyRules, '0.source'));
        $this->assertSame('+7 000 000-00-01', data_get($legacyRules, '0.phone'));
        $this->assertSame([
            [
                'name' => 'cpc',
                'phone' => '+7 000 000-00-03',
            ],
        ], data_get($legacyRules, '0.medium'));
    }

    /** @test */
    public function it_keeps_active_campaign_rules_nested_under_medium_in_legacy_city_json(): void
    {
        $city = City::query()->create([
            'name' => 'Киров',
            'slug' => 'kirov',
            'active' => true,
        ]);

        app(UtmTrackerService::class)->sync($city, [
            'phones' => [
                ['key' => 'phone-source', 'phone' => '+7 000 000-00-01'],
                ['key' => 'phone-medium', 'phone' => '+7 000 000-00-02'],
                ['key' => 'phone-campaign', 'phone' => '+7 000 000-00-03'],
                ['key' => 'phone-archive', 'phone' => '+7 000 000-00-04'],
            ],
            'sources' => [
                [
                    'key' => 'source-vk',
                    'source' => 'vk',
                    'name' => 'VK',
                    'default_phone_key' => 'phone-source',
                ],
            ],
            'campaigns' => [
                [
                    'key' => 'campaign-source',
                    'type' => 'source',
                    'source_key' => 'source-vk',
                    'phone_key' => 'phone-source',
                    'started_at' => now()->subDay()->format('Y-m-d H:i:s'),
                ],
                [
                    'key' => 'campaign-medium',
                    'type' => 'medium',
                    'source_key' => 'source-vk',
                    'medium' => 'banner',
                    'medium_name' => 'Banner',
                    'phone_key' => 'phone-medium',
                    'started_at' => now()->subHours(3)->format('Y-m-d H:i:s'),
                ],
                [
                    'key' => 'campaign-campaign',
                    'type' => 'medium',
                    'source_key' => 'source-vk',
                    'medium' => 'banner',
                    'medium_name' => 'Banner',
                    'campaign' => 'test',
                    'campaign_name' => 'Test campaign',
                    'phone_key' => 'phone-campaign',
                    'started_at' => now()->subHours(2)->format('Y-m-d H:i:s'),
                ],
            ],
            'archived_campaigns' => [
                [
                    'key' => 'campaign-archive',
                    'type' => 'campaign',
                    'source_key' => 'source-vk',
                    'medium' => 'banner',
                    'campaign' => 'old',
                    'phone_key' => 'phone-archive',
                    'started_at' => now()->subDays(5)->format('Y-m-d H:i:s'),
                    'stopped_at' => now()->subDay()->format('Y-m-d H:i:s'),
                    'archived_at' => now()->subDay()->format('Y-m-d H:i:s'),
                ],
            ],
        ]);

        $legacyRules = $city->fresh()->utm_phones;

        $this->assertDatabaseHas('city_utm_campaigns', [
            'city_id' => $city->id,
            'medium' => 'banner',
            'campaign' => 'test',
            'campaign_name' => 'Test campaign',
        ]);
        $this->assertSame('vk', data_get($legacyRules, '0.source'));
        $this->assertSame('banner', data_get($legacyRules, '0.medium.0.name'));
        $this->assertSame('+7 000 000-00-02', data_get($legacyRules, '0.medium.0.phone'));
        $this->assertSame([
            [
                'name' => 'test',
                'phone' => '+7 000 000-00-03',
            ],
        ], data_get($legacyRules, '0.medium.0.campaign'));
    }

    /** @test */
    public function it_backfills_campaigns_from_existing_sources_and_mediums_via_migration(): void
    {
        Schema::dropIfExists('city_utm_campaigns');

        $city = City::query()->create([
            'name' => 'Москва',
            'slug' => 'moskva',
            'active' => true,
        ]);

        $phone = CityUtmPhone::query()->create([
            'city_id' => $city->id,
            'phone' => '+7 000 000-00-01',
        ]);

        $source = CityUtmSource::query()->create([
            'city_id' => $city->id,
            'source' => 'google',
            'name' => 'Google',
            'default_phone_id' => $phone->id,
            'open_booking_widget' => true,
        ]);

        CityUtmMedium::query()->create([
            'city_id' => $city->id,
            'source_id' => $source->id,
            'medium' => 'cpc',
            'medium_name' => 'Google CPC',
            'phone_id' => $phone->id,
            'open_booking_widget' => true,
        ]);

        $migration = include database_path('migrations/2026_04_03_180000_create_city_utm_campaigns_table.php');
        $migration->up();

        $campaigns = CityUtmCampaign::query()
            ->where('city_id', $city->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $campaigns);
        $this->assertTrue($campaigns->contains(fn (CityUtmCampaign $campaign): bool => $campaign->medium === 'cpc' && $campaign->phone_id === $phone->id));
        $this->assertTrue($campaigns->contains(fn (CityUtmCampaign $campaign): bool => $campaign->medium === null && $campaign->phone_id === null && $campaign->open_booking_widget));
    }

    /** @test */
    public function it_returns_active_campaigns_sorted_by_created_at_desc(): void
    {
        $city = City::query()->create([
            'name' => 'Омск',
            'slug' => 'omsk',
            'active' => true,
        ]);

        $phone = CityUtmPhone::query()->create([
            'city_id' => $city->id,
            'phone' => '+7 000 000-00-01',
        ]);

        $source = CityUtmSource::query()->create([
            'city_id' => $city->id,
            'source' => 'google',
            'name' => 'Google',
        ]);

        $olderCampaign = CityUtmCampaign::query()->create([
            'city_id' => $city->id,
            'source_id' => $source->id,
            'medium' => 'older',
            'medium_name' => 'Older',
            'phone_id' => $phone->id,
            'started_at' => now()->subDays(3),
        ]);

        $newerCampaign = CityUtmCampaign::query()->create([
            'city_id' => $city->id,
            'source_id' => $source->id,
            'medium' => 'newer',
            'medium_name' => 'Newer',
            'phone_id' => null,
            'started_at' => now()->subDay(),
        ]);

        $olderCampaign->forceFill(['created_at' => now()->subDays(2)])->saveQuietly();
        $newerCampaign->forceFill(['created_at' => now()->subHour()])->saveQuietly();

        $state = app(UtmTrackerService::class)->getEditorState($city->fresh());

        $this->assertSame('newer', $state['campaigns'][0]['medium']);
        $this->assertSame('older', $state['campaigns'][1]['medium']);
    }

    /** @test */
    public function it_archives_campaign_when_it_is_moved_out_of_active_list(): void
    {
        $city = City::query()->create([
            'name' => 'Самара',
            'slug' => 'samara',
            'active' => true,
        ]);

        $service = app(UtmTrackerService::class);
        $sourceStartedAt = now()->subDay()->format('Y-m-d H:i:s');
        $mediumStartedAt = now()->subHours(3)->format('Y-m-d H:i:s');
        $archivedAt = now()->format('Y-m-d H:i:s');

        $service->sync($city, [
            'phones' => [
                ['key' => 'phone-source', 'phone' => '+7 000 000-00-01'],
                ['key' => 'phone-medium', 'phone' => '+7 000 000-00-02'],
            ],
            'sources' => [
                [
                    'key' => 'source-google',
                    'source' => 'google',
                    'name' => 'Google',
                    'default_phone_key' => 'phone-source',
                ],
            ],
            'campaigns' => [
                [
                    'key' => 'campaign-source',
                    'type' => 'source',
                    'source_key' => 'source-google',
                    'phone_key' => 'phone-source',
                    'started_at' => $sourceStartedAt,
                ],
                [
                    'key' => 'campaign-medium',
                    'type' => 'medium',
                    'source_key' => 'source-google',
                    'medium' => 'cpc',
                    'medium_name' => 'Google CPC',
                    'phone_key' => 'phone-medium',
                    'started_at' => $mediumStartedAt,
                ],
            ],
            'archived_campaigns' => [],
        ]);

        $state = $service->getEditorState($city->fresh());
        $activeMedium = collect($state['campaigns'])->firstWhere('type', 'medium');

        $state['campaigns'] = array_values(array_filter($state['campaigns'], fn (array $row): bool => $row['key'] !== $activeMedium['key']));
        $state['archived_campaigns'][] = [
            ...$activeMedium,
            'stopped_at' => $archivedAt,
            'archived_at' => $archivedAt,
        ];

        $service->sync($city->fresh(), $state);

        $campaign = CityUtmCampaign::query()->where('city_id', $city->id)->where('medium', 'cpc')->firstOrFail();

        $this->assertNotNull($campaign->archived_at);
        $this->assertSame($mediumStartedAt, $campaign->started_at?->format('Y-m-d H:i:s'));
        $this->assertCount(0, data_get($city->fresh()->utm_phones, '0.medium', []));
    }

    /** @test */
    public function it_bulk_archives_multiple_active_campaigns(): void
    {
        $city = City::query()->create([
            'name' => 'Курск',
            'slug' => 'kursk',
            'active' => true,
        ]);

        $service = app(UtmTrackerService::class);

        $service->sync($city, [
            'phones' => [
                ['key' => 'phone-google', 'phone' => '+7 000 000-00-01'],
                ['key' => 'phone-yandex', 'phone' => '+7 000 000-00-02'],
            ],
            'sources' => [
                [
                    'key' => 'source-google',
                    'source' => 'google',
                    'name' => 'Google',
                    'default_phone_key' => null,
                ],
                [
                    'key' => 'source-yandex',
                    'source' => 'yandex',
                    'name' => 'Yandex',
                    'default_phone_key' => null,
                ],
            ],
            'campaigns' => [
                [
                    'key' => 'campaign-google',
                    'type' => 'medium',
                    'source_key' => 'source-google',
                    'medium' => 'cpc',
                    'medium_name' => 'Google CPC',
                    'phone_key' => 'phone-google',
                    'started_at' => now()->subDay()->format('Y-m-d H:i:s'),
                ],
                [
                    'key' => 'campaign-yandex',
                    'type' => 'medium',
                    'source_key' => 'source-yandex',
                    'medium' => 'search',
                    'medium_name' => 'Yandex Search',
                    'phone_key' => 'phone-yandex',
                    'started_at' => now()->subHours(5)->format('Y-m-d H:i:s'),
                ],
            ],
            'archived_campaigns' => [],
        ]);

        $state = $service->getEditorState($city->fresh());
        $result = $service->stopCampaigns(
            $city->fresh(),
            $state,
            collect($state['campaigns'])->pluck('key')->all(),
        );

        $this->assertCount(0, $result['campaigns']);
        $this->assertCount(2, $result['archived_campaigns']);
        $this->assertDatabaseCount('city_utm_campaigns', 2);
        $this->assertDatabaseHas('city_utm_campaigns', [
            'city_id' => $city->id,
            'medium' => 'cpc',
        ]);
        $this->assertDatabaseHas('city_utm_campaigns', [
            'city_id' => $city->id,
            'medium' => 'search',
        ]);
        $this->assertEmpty($city->fresh()->utm_phones);
    }

    /** @test */
    public function it_creates_source_only_campaign_draft_from_source_default_phone_when_no_active_medium_exists(): void
    {
        $city = City::query()->create([
            'name' => 'Пермь',
            'slug' => 'perm',
            'active' => true,
        ]);

        $service = app(UtmTrackerService::class);

        $service->sync($city, [
            'phones' => [
                ['key' => 'phone-source', 'phone' => '+7 000 000-00-01'],
            ],
            'sources' => [
                [
                    'key' => 'source-google',
                    'source' => 'google',
                    'name' => 'Google',
                    'default_phone_key' => 'phone-source',
                ],
            ],
            'campaigns' => [],
            'archived_campaigns' => [],
        ]);

        $state = $service->getEditorState($city->fresh());

        $this->assertCount(1, $state['campaigns']);
        $this->assertSame('source', $state['campaigns'][0]['type']);
        $this->assertNull($state['campaigns'][0]['started_at']);
        $this->assertSame([], $city->fresh()->utm_phones);

        $service->launchCampaign($city->fresh(), $state, $state['campaigns'][0]['key']);

        $this->assertSame('+7 000 000-00-01', data_get($city->fresh()->utm_phones, '0.phone'));
    }

    /** @test */
    public function it_creates_new_campaign_record_when_archived_campaign_is_resumed(): void
    {
        $city = City::query()->create([
            'name' => 'Сочи',
            'slug' => 'sochi',
            'active' => true,
        ]);

        $service = app(UtmTrackerService::class);

        $service->sync($city, [
            'phones' => [
                ['key' => 'phone-medium', 'phone' => '+7 000 000-00-01'],
            ],
            'sources' => [
                [
                    'key' => 'source-google',
                    'source' => 'google',
                    'name' => 'Google',
                    'default_phone_key' => null,
                ],
            ],
            'campaigns' => [],
            'archived_campaigns' => [
                [
                    'key' => 'campaign-archive',
                    'type' => 'medium',
                    'source_key' => 'source-google',
                    'medium' => 'cpc',
                    'medium_name' => 'Google CPC',
                    'phone_key' => 'phone-medium',
                    'started_at' => now()->subDays(2)->format('Y-m-d H:i:s'),
                    'stopped_at' => now()->subDay()->format('Y-m-d H:i:s'),
                    'archived_at' => now()->subDay()->format('Y-m-d H:i:s'),
                ],
            ],
        ]);

        $state = $service->getEditorState($city->fresh());
        $archived = $state['archived_campaigns'][0];

        $state['campaigns'][] = [
            ...$archived,
            'key' => 'campaign-resumed',
            'id' => null,
            'started_at' => now()->format('Y-m-d H:i:s'),
            'stopped_at' => null,
            'archived_at' => null,
            'restarted_from_id' => $archived['id'],
        ];

        $service->sync($city->fresh(), $state);

        $campaigns = CityUtmCampaign::query()->where('city_id', $city->id)->orderBy('id')->get();
        $archivedCampaign = $campaigns->firstWhere('archived_at', '!=', null);
        $activeCampaign = $campaigns->firstWhere('archived_at', null);

        $this->assertCount(2, $campaigns);
        $this->assertNotNull($archivedCampaign);
        $this->assertNotNull($activeCampaign);
        $this->assertNotSame($archivedCampaign->id, $activeCampaign->id);
        $this->assertSame($archivedCampaign->id, $activeCampaign->restarted_from_id);
    }

    /** @test */
    public function it_removes_empty_source_when_last_archived_rule_is_deleted(): void
    {
        $city = City::query()->create([
            'name' => 'Пермь',
            'slug' => 'perm',
            'active' => true,
        ]);

        $service = app(UtmTrackerService::class);

        $service->sync($city, [
            'phones' => [
                ['key' => 'phone-old', 'phone' => '+7 000 000-00-03'],
            ],
            'sources' => [
                [
                    'key' => 'source-old',
                    'source' => 'old_source',
                    'name' => 'Old source',
                    'default_phone_key' => null,
                ],
            ],
            'campaigns' => [],
            'archived_campaigns' => [
                [
                    'key' => 'campaign-old',
                    'type' => 'medium',
                    'source_key' => 'source-old',
                    'medium' => 'old_medium',
                    'medium_name' => 'Old medium',
                    'phone_key' => 'phone-old',
                    'started_at' => now()->subDay()->format('Y-m-d H:i:s'),
                    'stopped_at' => now()->subHour()->format('Y-m-d H:i:s'),
                    'archived_at' => now()->subHour()->format('Y-m-d H:i:s'),
                ],
            ],
        ]);

        $state = $service->getEditorState($city->fresh());
        $result = $service->deleteArchivedCampaign($city->fresh(), $state, $state['archived_campaigns'][0]['key']);

        $this->assertCount(0, $result['campaigns']);
        $this->assertCount(0, $result['archived_campaigns']);
        $this->assertCount(0, $result['sources']);
        $this->assertDatabaseMissing('city_utm_sources', [
            'city_id' => $city->id,
            'source' => 'old_source',
        ]);
    }

    /** @test */
    public function it_merges_duplicate_sources_and_keeps_nested_medium_rule(): void
    {
        $city = City::query()->create([
            'name' => 'Казань',
            'slug' => 'kazan',
            'active' => true,
        ]);

        $service = app(UtmTrackerService::class);

        $service->sync($city, [
            'phones' => [
                ['key' => 'phone-medium', 'phone' => '+7 000 000-00-04'],
            ],
            'sources' => [
                [
                    'key' => 'source-huj',
                    'source' => 'huj',
                    'name' => 'Huj',
                    'default_phone_key' => null,
                ],
            ],
            'campaigns' => [],
            'archived_campaigns' => [],
        ]);

        $state = $service->getEditorState($city->fresh());
        $phoneKey = $state['phones'][0]['key'];
        $state['sources'][] = [
            'key' => 'source-huj-duplicate',
            'id' => null,
            'source' => 'huj',
            'name' => 'Huj duplicate',
            'default_phone_key' => null,
            'open_booking_widget' => false,
            'is_organic' => false,
        ];
        $state['campaigns'][] = [
            'key' => 'campaign-subhuj',
            'type' => 'medium',
            'source_key' => 'source-huj-duplicate',
            'medium' => 'subhuj',
            'medium_name' => 'Subhuj',
            'phone_key' => $phoneKey,
            'started_at' => null,
        ];

        $result = $service->saveEditorState($city->fresh(), $state);

        $this->assertCount(1, $result['sources']);
        $this->assertSame('huj', $result['sources'][0]['source']);
        $this->assertCount(1, $result['campaigns']);
        $this->assertSame($result['sources'][0]['key'], $result['campaigns'][0]['source_key']);
        $this->assertSame('subhuj', $result['campaigns'][0]['medium']);
        $this->assertSame(1, CityUtmSource::query()->where('city_id', $city->id)->where('source', 'huj')->count());
    }

    /** @test */
    public function it_soft_deletes_active_campaign_by_moving_it_to_archive(): void
    {
        $city = City::query()->create([
            'name' => 'Белгород',
            'slug' => 'belgorod',
            'active' => true,
        ]);

        $service = app(UtmTrackerService::class);

        $service->sync($city, [
            'phones' => [
                ['key' => 'phone-google', 'phone' => '+7 000 000-00-01'],
            ],
            'sources' => [
                [
                    'key' => 'source-google',
                    'source' => 'google',
                    'name' => 'Google',
                    'default_phone_key' => null,
                ],
            ],
            'campaigns' => [
                [
                    'key' => 'campaign-google',
                    'type' => 'medium',
                    'source_key' => 'source-google',
                    'medium' => 'cpc',
                    'medium_name' => 'Google CPC',
                    'phone_key' => 'phone-google',
                    'started_at' => now()->subHours(2)->format('Y-m-d H:i:s'),
                ],
            ],
            'archived_campaigns' => [],
        ]);

        $state = $service->getEditorState($city->fresh());
        $campaignKey = $state['campaigns'][0]['key'];
        $result = $service->deleteCampaign($city->fresh(), $state, $campaignKey);

        $this->assertCount(0, $result['campaigns']);
        $this->assertCount(1, $result['archived_campaigns']);

        $campaign = CityUtmCampaign::query()
            ->where('city_id', $city->id)
            ->where('medium', 'cpc')
            ->firstOrFail();

        $this->assertNotNull($campaign->archived_at);
        $this->assertNotNull($campaign->stopped_at);
        $this->assertEmpty($city->fresh()->utm_phones);
    }

    /** @test */
    public function it_keeps_source_only_campaign_visible_when_source_has_active_medium(): void
    {
        $city = City::query()->create([
            'name' => 'Тула',
            'slug' => 'tula',
            'active' => true,
        ]);

        $service = app(UtmTrackerService::class);

        $service->sync($city, [
            'phones' => [
                ['key' => 'phone-source', 'phone' => '+7 000 000-00-01'],
                ['key' => 'phone-medium', 'phone' => '+7 000 000-00-02'],
            ],
            'sources' => [
                [
                    'key' => 'source-google',
                    'source' => 'google',
                    'name' => 'Google',
                    'default_phone_key' => 'phone-source',
                ],
            ],
            'campaigns' => [
                [
                    'key' => 'campaign-medium',
                    'type' => 'medium',
                    'source_key' => 'source-google',
                    'medium' => 'cpc',
                    'medium_name' => 'Google CPC',
                    'phone_key' => 'phone-medium',
                    'started_at' => now()->subHour()->format('Y-m-d H:i:s'),
                ],
            ],
            'archived_campaigns' => [],
        ]);

        $state = $service->getEditorState($city->fresh());

        $this->assertCount(2, $state['campaigns']);
        $this->assertTrue(collect($state['campaigns'])->contains(fn (array $row): bool => $row['type'] === 'source'));
        $this->assertTrue(collect($state['campaigns'])->contains(fn (array $row): bool => $row['type'] === 'medium'));
        $this->assertNull(data_get($city->fresh()->utm_phones, '0.phone'));
        $this->assertSame('cpc', data_get($city->fresh()->utm_phones, '0.medium.0.name'));
    }

    /** @test */
    public function it_allows_reusing_phone_after_previous_campaign_is_archived(): void
    {
        $city = City::query()->create([
            'name' => 'Казань',
            'slug' => 'kazan',
            'active' => true,
        ]);

        $service = app(UtmTrackerService::class);

        $service->sync($city, [
            'phones' => [
                ['key' => 'phone-shared', 'phone' => '+7 000 000-00-01'],
            ],
            'sources' => [
                [
                    'key' => 'source-google',
                    'source' => 'google',
                    'name' => 'Google',
                    'default_phone_key' => null,
                ],
                [
                    'key' => 'source-yandex',
                    'source' => 'yandex',
                    'name' => 'Yandex',
                    'default_phone_key' => null,
                ],
            ],
            'campaigns' => [
                [
                    'key' => 'campaign-google',
                    'type' => 'medium',
                    'source_key' => 'source-google',
                    'medium' => 'cpc',
                    'medium_name' => 'Google CPC',
                    'phone_key' => 'phone-shared',
                    'started_at' => now()->subHours(3)->format('Y-m-d H:i:s'),
                ],
            ],
            'archived_campaigns' => [],
        ]);

        $state = $service->getEditorState($city->fresh());
        $active = $state['campaigns'][0];
        $yandexSourceKey = collect($state['sources'])->firstWhere('source', 'yandex')['key'];
        $sharedPhoneKey = $active['phone_key'];

        $state['campaigns'] = [];
        $state['archived_campaigns'][] = [
            ...$active,
            'stopped_at' => now()->subHour()->format('Y-m-d H:i:s'),
            'archived_at' => now()->subHour()->format('Y-m-d H:i:s'),
        ];
        $state['campaigns'][] = [
            'key' => 'campaign-yandex',
            'id' => null,
            'type' => 'medium',
            'source_key' => $yandexSourceKey,
            'medium' => 'search',
            'medium_name' => 'Yandex Search',
            'phone_key' => $sharedPhoneKey,
            'open_booking_widget' => false,
            'started_at' => now()->format('Y-m-d H:i:s'),
            'stopped_at' => null,
            'archived_at' => null,
            'restarted_from_id' => null,
        ];

        $service->sync($city->fresh(), $state);

        $legacyRules = $city->fresh()->utm_phones;

        $this->assertSame('yandex', data_get($legacyRules, '0.source'));
        $this->assertSame('search', data_get($legacyRules, '0.medium.0.name'));
        $this->assertSame('+7 000 000-00-01', data_get($legacyRules, '0.medium.0.phone'));
    }

    /** @test */
    public function it_allows_saving_other_changes_when_legacy_active_campaign_duplicates_are_unchanged(): void
    {
        $city = City::query()->create([
            'name' => 'Москва',
            'slug' => 'moskva',
            'active' => true,
        ]);

        $sharedPhone = CityUtmPhone::query()->create([
            'city_id' => $city->id,
            'phone' => '+7 000 000-00-01',
        ]);

        $freePhone = CityUtmPhone::query()->create([
            'city_id' => $city->id,
            'phone' => '+7 000 000-00-02',
        ]);

        $sourceGoogle = CityUtmSource::query()->create([
            'city_id' => $city->id,
            'source' => 'google',
            'name' => 'Google',
            'default_phone_id' => null,
        ]);

        $sourceYandex = CityUtmSource::query()->create([
            'city_id' => $city->id,
            'source' => 'yandex',
            'name' => 'Yandex',
            'default_phone_id' => null,
        ]);

        CityUtmCampaign::query()->create([
            'city_id' => $city->id,
            'source_id' => $sourceGoogle->id,
            'medium' => 'cpc',
            'medium_name' => 'Google CPC',
            'phone_id' => $sharedPhone->id,
            'started_at' => now()->subDay(),
        ]);

        CityUtmCampaign::query()->create([
            'city_id' => $city->id,
            'source_id' => $sourceYandex->id,
            'medium' => 'search',
            'medium_name' => 'Yandex Search',
            'phone_id' => $sharedPhone->id,
            'started_at' => now()->subDay(),
        ]);

        $service = app(UtmTrackerService::class);
        $state = $service->getEditorState($city);
        $state['phones'][] = [
            'key' => 'phone-new',
            'id' => null,
            'phone' => '+7 000 000-00-03',
        ];

        $service->sync($city->fresh(), $state);

        $this->assertDatabaseHas('city_utm_phones', [
            'city_id' => $city->id,
            'phone' => '+7 000 000-00-03',
        ]);
    }

    /** @test */
    public function it_persists_phone_active_flag_in_editor_state(): void
    {
        $city = City::query()->create([
            'name' => 'Ижевск',
            'slug' => 'izhevsk',
            'active' => true,
        ]);

        $service = app(UtmTrackerService::class);

        $service->sync($city, [
            'phones' => [
                ['key' => 'phone-active', 'phone' => '+7 000 000-00-01'],
                ['key' => 'phone-inactive', 'phone' => '+7 000 000-00-02', 'is_active' => false],
            ],
            'sources' => [],
            'campaigns' => [],
            'archived_campaigns' => [],
        ]);

        $this->assertDatabaseHas('city_utm_phones', [
            'city_id' => $city->id,
            'phone' => '+7 000 000-00-01',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('city_utm_phones', [
            'city_id' => $city->id,
            'phone' => '+7 000 000-00-02',
            'is_active' => false,
        ]);

        $state = $service->getEditorState($city->fresh());

        $this->assertTrue(collect($state['phones'])->firstWhere('phone', '+7 000 000-00-01')['is_active']);
        $this->assertFalse(collect($state['phones'])->firstWhere('phone', '+7 000 000-00-02')['is_active']);
    }

    /** @test */
    public function it_persists_widget_flags_in_campaign_state_without_changing_legacy_json_shape(): void
    {
        $city = City::query()->create([
            'name' => 'Тверь',
            'slug' => 'tver',
            'active' => true,
        ]);

        $service = app(UtmTrackerService::class);

        $service->sync($city, [
            'phones' => [
                ['key' => 'phone-source', 'phone' => '+7 000 000-00-01'],
                ['key' => 'phone-medium', 'phone' => '+7 000 000-00-02'],
                ['key' => 'phone-medium-source', 'phone' => '+7 000 000-00-03'],
            ],
            'sources' => [
                [
                    'key' => 'source-solo',
                    'source' => 'solo',
                    'name' => 'Solo',
                    'default_phone_key' => 'phone-source',
                    'open_booking_widget' => true,
                    'is_organic' => true,
                ],
                [
                    'key' => 'source-google',
                    'source' => 'google',
                    'name' => 'Google',
                    'default_phone_key' => 'phone-medium-source',
                ],
            ],
            'campaigns' => [
                [
                    'key' => 'campaign-source',
                    'type' => 'source',
                    'source_key' => 'source-google',
                    'phone_key' => 'phone-source',
                    'open_booking_widget' => true,
                    'started_at' => now()->subHour()->format('Y-m-d H:i:s'),
                ],
                [
                    'key' => 'campaign-medium',
                    'type' => 'medium',
                    'source_key' => 'source-google',
                    'medium' => 'cpc',
                    'medium_name' => 'Google CPC',
                    'phone_key' => 'phone-medium',
                    'open_booking_widget' => true,
                    'is_organic' => true,
                    'is_organic_overridden' => true,
                    'started_at' => now()->format('Y-m-d H:i:s'),
                ],
            ],
            'archived_campaigns' => [],
        ]);

        $editorState = $service->getEditorState($city->fresh());

        $this->assertCount(3, $editorState['campaigns']);
        $this->assertTrue((bool) collect($editorState['campaigns'])->firstWhere('type', 'source')['open_booking_widget']);
        $this->assertTrue((bool) collect($editorState['campaigns'])->firstWhere('type', 'medium')['open_booking_widget']);
        $this->assertTrue((bool) collect($editorState['sources'])->firstWhere('source', 'solo')['is_organic']);
        $this->assertTrue((bool) collect($editorState['campaigns'])->firstWhere('type', 'medium')['is_organic']);
        $this->assertTrue((bool) collect($editorState['campaigns'])->firstWhere('type', 'medium')['is_organic_overridden']);
        $this->assertTrue((bool) collect($editorState['campaigns'])->firstWhere('type', 'medium')['effective_is_organic']);
        $this->assertNull(data_get($city->fresh()->utm_phones, '0.open_booking_widget'));
        $this->assertNull(data_get($city->fresh()->utm_phones, '0.medium.0.open_booking_widget'));
        $this->assertNull(data_get($city->fresh()->utm_phones, '0.is_organic'));
        $this->assertNull(data_get($city->fresh()->utm_phones, '0.medium.0.is_organic'));
    }

    /** @test */
    public function it_persists_vk_app_and_cabinet_fields_without_changing_legacy_json_shape(): void
    {
        $city = City::query()->create([
            'name' => 'Самара',
            'slug' => 'samara',
            'active' => true,
        ]);

        $service = app(UtmTrackerService::class);

        $service->sync($city, [
            'phones' => [
                ['key' => 'phone-source', 'phone' => '+7 000 000-00-01'],
                ['key' => 'phone-medium', 'phone' => '+7 000 000-00-02'],
                ['key' => 'phone-campaign', 'phone' => '+7 000 000-00-03'],
            ],
            'sources' => [
                [
                    'key' => 'source-vk',
                    'source' => 'vk',
                    'name' => 'VK',
                    'default_phone_key' => 'phone-source',
                ],
            ],
            'campaigns' => [
                [
                    'key' => 'campaign-source',
                    'type' => 'source',
                    'source_key' => 'source-vk',
                    'phone_key' => 'phone-source',
                    'cabinet' => 'direct',
                    'vk_app_enabled' => true,
                    'started_at' => now()->subHours(3)->format('Y-m-d H:i:s'),
                ],
                [
                    'key' => 'campaign-medium',
                    'type' => 'medium',
                    'source_key' => 'source-vk',
                    'medium' => 'post',
                    'phone_key' => 'phone-medium',
                    'cabinet' => 'invalid',
                    'vk_app_enabled' => false,
                    'started_at' => now()->subHours(2)->format('Y-m-d H:i:s'),
                ],
                [
                    'key' => 'campaign-campaign',
                    'type' => 'campaign',
                    'source_key' => 'source-vk',
                    'medium' => 'post',
                    'campaign' => 'brand',
                    'phone_key' => 'phone-campaign',
                    'cabinet' => 'target',
                    'vk_app_enabled' => true,
                    'started_at' => now()->subHour()->format('Y-m-d H:i:s'),
                ],
            ],
            'archived_campaigns' => [
                [
                    'key' => 'campaign-archive',
                    'type' => 'medium',
                    'source_key' => 'source-vk',
                    'medium' => 'old',
                    'phone_key' => 'phone-medium',
                    'cabinet' => 'y_business',
                    'vk_app_enabled' => true,
                    'started_at' => now()->subDays(2)->format('Y-m-d H:i:s'),
                    'stopped_at' => now()->subDay()->format('Y-m-d H:i:s'),
                    'archived_at' => now()->subDay()->format('Y-m-d H:i:s'),
                ],
            ],
        ]);

        $editorState = $service->getEditorState($city->fresh());

        $sourceRow = collect($editorState['campaigns'])->firstWhere('type', 'source');
        $mediumRow = collect($editorState['campaigns'])->firstWhere('type', 'medium');
        $campaignRow = collect($editorState['campaigns'])->firstWhere('type', 'campaign');
        $archivedRow = collect($editorState['archived_campaigns'])->firstWhere('medium', 'old');

        $this->assertSame('direct', $sourceRow['cabinet']);
        $this->assertTrue((bool) $sourceRow['vk_app_enabled']);
        $this->assertNull($mediumRow['cabinet']);
        $this->assertSame('target', $campaignRow['cabinet']);
        $this->assertTrue((bool) $campaignRow['vk_app_enabled']);
        $this->assertSame('y_business', $archivedRow['cabinet']);
        $this->assertTrue((bool) $archivedRow['vk_app_enabled']);

        $this->assertDatabaseHas('city_utm_campaigns', [
            'medium' => 'post',
            'campaign' => 'brand',
            'cabinet' => 'target',
            'vk_app_enabled' => true,
        ]);
        $this->assertDatabaseHas('city_utm_campaigns', [
            'medium' => 'old',
            'cabinet' => 'y_business',
            'vk_app_enabled' => true,
        ]);

        $legacy = $city->fresh()->utm_phones;
        $this->assertNull(data_get($legacy, '0.cabinet'));
        $this->assertNull(data_get($legacy, '0.vk_app_enabled'));
        $this->assertNull(data_get($legacy, '0.medium.0.cabinet'));
        $this->assertNull(data_get($legacy, '0.medium.0.vk_app_enabled'));
        $this->assertNull(data_get($legacy, '0.medium.0.campaign.0.cabinet'));
        $this->assertNull(data_get($legacy, '0.medium.0.campaign.0.vk_app_enabled'));
    }

    /** @test */
    public function it_persists_campaign_metadata_without_phone_and_makes_vk_app_exclusive_with_widget(): void
    {
        $city = City::query()->create([
            'name' => 'Пермь',
            'slug' => 'perm',
            'active' => true,
        ]);

        $service = app(UtmTrackerService::class);

        $service->sync($city, [
            'phones' => [
                ['key' => 'phone-source', 'phone' => '+7 000 000-00-01'],
            ],
            'sources' => [
                [
                    'key' => 'source-vk',
                    'source' => 'vk',
                    'name' => 'VK',
                    'default_phone_key' => null,
                    'open_booking_widget' => true,
                ],
            ],
            'campaigns' => [
                [
                    'key' => 'campaign-source',
                    'type' => 'source',
                    'source_key' => 'source-vk',
                    'phone_key' => null,
                    'open_booking_widget' => true,
                    'cabinet' => 'direct',
                    'vk_app_enabled' => true,
                    'started_at' => now()->format('Y-m-d H:i:s'),
                ],
                [
                    'key' => 'campaign-medium',
                    'type' => 'medium',
                    'source_key' => 'source-vk',
                    'medium' => 'post',
                    'phone_key' => null,
                    'open_booking_widget' => true,
                    'cabinet' => 'target',
                    'vk_app_enabled' => true,
                    'started_at' => now()->format('Y-m-d H:i:s'),
                ],
            ],
            'archived_campaigns' => [],
        ]);

        $this->assertDatabaseCount('city_utm_campaigns', 2);
        $this->assertDatabaseHas('city_utm_campaigns', [
            'medium' => null,
            'phone_id' => null,
            'cabinet' => 'direct',
            'vk_app_enabled' => true,
            'open_booking_widget' => false,
        ]);
        $this->assertDatabaseHas('city_utm_campaigns', [
            'medium' => 'post',
            'phone_id' => null,
            'cabinet' => 'target',
            'vk_app_enabled' => true,
            'open_booking_widget' => false,
        ]);

        $editorRows = collect($service->getEditorState($city->fresh())['campaigns']);

        $this->assertTrue((bool) $editorRows->firstWhere('type', 'source')['vk_app_enabled']);
        $this->assertFalse((bool) $editorRows->firstWhere('type', 'source')['open_booking_widget']);
        $this->assertTrue((bool) $editorRows->firstWhere('type', 'medium')['vk_app_enabled']);
        $this->assertFalse((bool) $editorRows->firstWhere('type', 'medium')['open_booking_widget']);
    }

    /** @test */
    public function it_persists_and_launches_medium_and_campaign_without_own_phone_when_source_has_default_phone(): void
    {
        $city = City::query()->create([
            'name' => 'Омск',
            'slug' => 'omsk',
            'active' => true,
        ]);

        $service = app(UtmTrackerService::class);

        $draftState = [
            'phones' => [
                ['key' => 'phone-source', 'phone' => '+7 000 000-00-09'],
            ],
            'sources' => [
                [
                    'key' => 'source-vk',
                    'source' => 'vk',
                    'name' => 'VK',
                    'default_phone_key' => 'phone-source',
                    'open_booking_widget' => false,
                ],
            ],
            'campaigns' => [
                [
                    'key' => 'campaign-medium',
                    'type' => 'medium',
                    'source_key' => 'source-vk',
                    'medium' => 'post',
                    'phone_key' => null,
                    'started_at' => null,
                ],
                [
                    'key' => 'campaign-campaign',
                    'type' => 'campaign',
                    'source_key' => 'source-vk',
                    'medium' => 'post',
                    'campaign' => 'test',
                    'phone_key' => null,
                    'started_at' => null,
                ],
            ],
            'archived_campaigns' => [],
        ];

        $savedDraft = $service->saveEditorState($city, $draftState);

        $mediumRow = collect($savedDraft['campaigns'])->firstWhere('type', 'medium');
        $campaignRow = collect($savedDraft['campaigns'])->firstWhere('type', 'campaign');

        $this->assertNotNull($mediumRow);
        $this->assertNotNull($campaignRow);
        $this->assertNull($mediumRow['phone_key']);
        $this->assertNull($campaignRow['phone_key']);

        $launchedState = $service->launchCampaigns($city->fresh(), $savedDraft, [$mediumRow['key'], $campaignRow['key']]);

        $launchedMedium = collect($launchedState['campaigns'])->firstWhere('key', $mediumRow['key']);
        $launchedCampaign = collect($launchedState['campaigns'])->firstWhere('key', $campaignRow['key']);

        $this->assertNotNull($launchedMedium['started_at']);
        $this->assertNotNull($launchedCampaign['started_at']);
        $this->assertSame('vk', data_get($city->fresh()->utm_phones, '0.source'));
        $this->assertSame('post', data_get($city->fresh()->utm_phones, '0.medium.0.name'));
        $this->assertSame('test', data_get($city->fresh()->utm_phones, '0.medium.0.campaign.0.name'));
    }

    /** @test */
    public function it_keeps_phone_rows_as_drafts_until_they_are_explicitly_launched(): void
    {
        $city = City::query()->create([
            'name' => 'Рязань',
            'slug' => 'ryazan',
            'active' => true,
        ]);

        $service = app(UtmTrackerService::class);

        $draftState = [
            'phones' => [
                ['key' => 'phone-main', 'phone' => '+7 000 000-00-01'],
            ],
            'sources' => [
                [
                    'key' => 'source-vk',
                    'source' => 'vk',
                    'name' => 'VK',
                    'default_phone_key' => null,
                    'open_booking_widget' => false,
                ],
            ],
            'campaigns' => [
                [
                    'key' => 'campaign-post',
                    'type' => 'medium',
                    'source_key' => 'source-vk',
                    'medium' => 'post',
                    'phone_key' => 'phone-main',
                    'open_booking_widget' => false,
                    'vk_app_enabled' => true,
                    'started_at' => null,
                ],
            ],
            'archived_campaigns' => [],
        ];

        $savedDraft = $service->saveEditorState($city, $draftState);
        $draftRow = collect($savedDraft['campaigns'])->firstWhere('medium', 'post');

        $this->assertNull($draftRow['started_at']);
        $this->assertSame([], $city->fresh()->utm_phones);

        $launchedState = $service->launchCampaign($city->fresh(), $savedDraft, $draftRow['key']);
        $launchedRow = collect($launchedState['campaigns'])->firstWhere('medium', 'post');

        $this->assertNotNull($launchedRow['started_at']);
        $this->assertSame('vk', data_get($city->fresh()->utm_phones, '0.source'));
        $this->assertSame('post', data_get($city->fresh()->utm_phones, '0.medium.0.name'));
    }

    /** @test */
    public function it_inherits_organic_flag_from_source_until_campaign_overrides_it(): void
    {
        $city = City::query()->create([
            'name' => 'Казань',
            'slug' => 'kazan',
            'active' => true,
        ]);

        $service = app(UtmTrackerService::class);

        $service->sync($city, [
            'phones' => [
                ['key' => 'phone-source', 'phone' => '+7 000 000-00-01'],
                ['key' => 'phone-medium', 'phone' => '+7 000 000-00-02'],
            ],
            'sources' => [
                [
                    'key' => 'source-yandex',
                    'source' => 'yandex',
                    'name' => 'Яндекс.Директ',
                    'default_phone_key' => 'phone-source',
                    'is_organic' => true,
                ],
            ],
            'campaigns' => [
                [
                    'key' => 'campaign-medium',
                    'type' => 'medium',
                    'source_key' => 'source-yandex',
                    'medium' => 'cpc',
                    'phone_key' => 'phone-medium',
                    'started_at' => now()->format('Y-m-d H:i:s'),
                ],
            ],
            'archived_campaigns' => [],
        ]);

        $editorState = $service->getEditorState($city->fresh());
        $mediumRow = collect($editorState['campaigns'])->firstWhere('type', 'medium');

        $this->assertFalse((bool) $mediumRow['is_organic_overridden']);
        $this->assertFalse((bool) $mediumRow['is_organic']);
        $this->assertTrue((bool) $mediumRow['effective_is_organic']);

        $mediumRow['is_organic'] = false;
        $mediumRow['is_organic_overridden'] = true;
        $editorState['campaigns'] = collect($editorState['campaigns'])
            ->map(fn (array $row): array => $row['key'] === $mediumRow['key'] ? $mediumRow : $row)
            ->all();

        $service->sync($city->fresh(), $editorState);
        $editorState = $service->getEditorState($city->fresh());
        $mediumRow = collect($editorState['campaigns'])->firstWhere('type', 'medium');

        $this->assertTrue((bool) $mediumRow['is_organic_overridden']);
        $this->assertFalse((bool) $mediumRow['is_organic']);
        $this->assertFalse((bool) $mediumRow['effective_is_organic']);
        $this->assertTrue((bool) collect($editorState['sources'])->firstWhere('source', 'yandex')['is_organic']);
    }

    /** @test */
    public function it_normalizes_invalid_archived_campaign_dates_when_stop_time_is_earlier_than_start(): void
    {
        $city = City::query()->create([
            'name' => 'Омск',
            'slug' => 'omsk',
            'active' => true,
        ]);

        $phone = CityUtmPhone::query()->create([
            'city_id' => $city->id,
            'phone' => '+7 000 000-00-01',
        ]);

        $source = CityUtmSource::query()->create([
            'city_id' => $city->id,
            'source' => '2gisads',
            'name' => '2GIS',
        ]);

        $campaign = CityUtmCampaign::query()->create([
            'city_id' => $city->id,
            'source_id' => $source->id,
            'medium' => 'night',
            'medium_name' => 'Night',
            'phone_id' => $phone->id,
            'started_at' => now()->format('Y-m-d H:i:s'),
            'stopped_at' => now()->subSeconds(2)->format('Y-m-d H:i:s'),
            'archived_at' => now()->subSeconds(2)->format('Y-m-d H:i:s'),
        ]);

        $service = app(UtmTrackerService::class);
        $state = $service->getEditorState($city);

        $service->sync($city->fresh(), $state);

        $campaign->refresh();

        $this->assertNotNull($campaign->stopped_at);
        $this->assertTrue($campaign->stopped_at->greaterThanOrEqualTo($campaign->started_at));
        $this->assertTrue($campaign->archived_at->greaterThanOrEqualTo($campaign->stopped_at));
    }
}
