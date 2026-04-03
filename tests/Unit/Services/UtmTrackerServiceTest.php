<?php

namespace Tests\Unit\Services;

use App\Models\City;
use App\Models\CityUtmMedium;
use App\Models\CityUtmPhone;
use App\Models\CityUtmSource;
use App\Services\UtmTrackerService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
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
    public function it_keeps_only_active_medium_rules_in_legacy_city_json(): void
    {
        $city = City::query()->create([
            'name' => 'Киров',
            'slug' => 'kirov',
            'active' => true,
        ]);

        app(UtmTrackerService::class)->sync($city, [
            'phones' => [
                ['key' => 'phone-source', 'phone' => '+7 000 000-00-01'],
                ['key' => 'phone-active', 'phone' => '+7 000 000-00-02'],
                ['key' => 'phone-stopped', 'phone' => '+7 000 000-00-03'],
                ['key' => 'phone-scheduled', 'phone' => '+7 000 000-00-04'],
            ],
            'sources' => [
                [
                    'key' => 'source-google',
                    'source' => 'google',
                    'name' => 'Google Ads',
                    'default_phone_key' => 'phone-source',
                ],
            ],
            'mediums' => [
                [
                    'key' => 'medium-active',
                    'source_key' => 'source-google',
                    'medium' => 'cpc',
                    'medium_name' => 'Active',
                    'phone_key' => 'phone-active',
                    'start_date' => now()->subDay()->format('Y-m-d'),
                    'end_date' => null,
                ],
                [
                    'key' => 'medium-stopped',
                    'source_key' => 'source-google',
                    'medium' => 'pause',
                    'medium_name' => 'Stopped',
                    'phone_key' => 'phone-stopped',
                    'start_date' => now()->subDays(10)->format('Y-m-d'),
                    'end_date' => now()->format('Y-m-d'),
                ],
                [
                    'key' => 'medium-scheduled',
                    'source_key' => 'source-google',
                    'medium' => 'future',
                    'medium_name' => 'Scheduled',
                    'phone_key' => 'phone-scheduled',
                    'start_date' => now()->addDay()->format('Y-m-d'),
                    'end_date' => null,
                ],
            ],
        ]);

        $legacyRules = $city->fresh()->utm_phones;

        $this->assertSame('google', data_get($legacyRules, '0.source'));
        $this->assertSame('+7 000 000-00-01', data_get($legacyRules, '0.phone'));
        $this->assertSame([
            [
                'name' => 'cpc',
                'phone' => '+7 000 000-00-02',
            ],
        ], data_get($legacyRules, '0.medium'));
    }

    /** @test */
    public function it_allows_source_default_phone_to_match_phone_of_its_own_medium(): void
    {
        $city = City::query()->create([
            'name' => 'Москва',
            'slug' => 'moskva',
            'active' => true,
        ]);

        app(UtmTrackerService::class)->sync($city, [
            'phones' => [
                ['key' => 'phone-shared', 'phone' => '+7 000 000-00-01'],
            ],
            'sources' => [
                [
                    'key' => 'source-google',
                    'source' => 'google',
                    'name' => 'Google',
                    'default_phone_key' => 'phone-shared',
                ],
            ],
            'mediums' => [
                [
                    'key' => 'medium-cpc',
                    'source_key' => 'source-google',
                    'medium' => 'cpc',
                    'medium_name' => 'Google CPC',
                    'phone_key' => 'phone-shared',
                    'start_date' => null,
                    'end_date' => null,
                ],
            ],
        ]);

        $city = $city->fresh(['utmSources.defaultPhone']);

        $this->assertNotNull($city->utmSources->first()?->default_phone_id);
        $this->assertSame('+7 000 000-00-01', data_get($city->utm_phones, '0.phone'));
        $this->assertSame('+7 000 000-00-01', data_get($city->utm_phones, '0.medium.0.phone'));
    }

    /** @test */
    public function it_clears_source_default_phone_when_same_phone_is_used_by_foreign_medium(): void
    {
        $city = City::query()->create([
            'name' => 'Москва',
            'slug' => 'moskva',
            'active' => true,
        ]);

        app(UtmTrackerService::class)->sync($city, [
            'phones' => [
                ['key' => 'phone-shared', 'phone' => '+7 000 000-00-01'],
                ['key' => 'phone-second', 'phone' => '+7 000 000-00-02'],
            ],
            'sources' => [
                [
                    'key' => 'source-sms',
                    'source' => 'sms',
                    'name' => 'SMS',
                    'default_phone_key' => 'phone-shared',
                ],
                [
                    'key' => 'source-google',
                    'source' => 'google',
                    'name' => 'Google',
                    'default_phone_key' => 'phone-second',
                ],
            ],
            'mediums' => [
                [
                    'key' => 'medium-cpc',
                    'source_key' => 'source-google',
                    'medium' => 'cpc',
                    'medium_name' => 'Google CPC',
                    'phone_key' => 'phone-shared',
                    'start_date' => null,
                    'end_date' => null,
                ],
            ],
        ]);

        $city = $city->fresh(['utmSources']);
        $googleRule = collect($city->utm_phones)->firstWhere('source', 'google');

        $this->assertNull($city->utmSources->firstWhere('source', 'sms')?->default_phone_id);
        $this->assertNull(collect($city->utm_phones)->firstWhere('source', 'sms')['phone'] ?? null);
        $this->assertSame('+7 000 000-00-01', data_get($googleRule, 'medium.0.phone'));
    }

    /** @test */
    public function it_rejects_duplicate_phone_usage_between_medium_rules(): void
    {
        $city = City::query()->create([
            'name' => 'Самара',
            'slug' => 'samara',
            'active' => true,
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Телефон нельзя использовать повторно');

        app(UtmTrackerService::class)->sync($city, [
            'phones' => [
                ['key' => 'phone-1', 'phone' => '+7 000 000-00-01'],
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
            'mediums' => [
                [
                    'key' => 'medium-google',
                    'source_key' => 'source-google',
                    'medium' => 'cpc',
                    'medium_name' => 'Google CPC',
                    'phone_key' => 'phone-1',
                    'start_date' => null,
                    'end_date' => null,
                ],
                [
                    'key' => 'medium-yandex',
                    'source_key' => 'source-yandex',
                    'medium' => 'search',
                    'medium_name' => 'Yandex Search',
                    'phone_key' => 'phone-1',
                    'start_date' => null,
                    'end_date' => null,
                ],
            ],
        ]);
    }

    /** @test */
    public function it_allows_saving_other_changes_when_legacy_medium_duplicates_are_unchanged(): void
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

        $sourceA = CityUtmSource::query()->create([
            'city_id' => $city->id,
            'source' => 'google',
            'name' => null,
            'default_phone_id' => null,
        ]);

        $sourceB = CityUtmSource::query()->create([
            'city_id' => $city->id,
            'source' => 'yandex',
            'name' => null,
            'default_phone_id' => $freePhone->id,
        ]);

        CityUtmMedium::query()->create([
            'city_id' => $city->id,
            'source_id' => $sourceA->id,
            'medium' => 'cpc',
            'medium_name' => null,
            'phone_id' => $sharedPhone->id,
        ]);

        CityUtmMedium::query()->create([
            'city_id' => $city->id,
            'source_id' => $sourceB->id,
            'medium' => 'search',
            'medium_name' => null,
            'phone_id' => $sharedPhone->id,
        ]);

        $service = app(UtmTrackerService::class);
        $state = $service->getEditorState($city);
        $state['phones'][] = [
            'key' => 'phone-new',
            'id' => null,
            'phone' => '+7 000 000-00-03',
        ];

        $service->sync($city, $state);

        $this->assertDatabaseHas('city_utm_phones', [
            'city_id' => $city->id,
            'phone' => '+7 000 000-00-03',
        ]);
    }

    /** @test */
    public function it_persists_widget_flags_in_editor_state_without_changing_legacy_json_shape(): void
    {
        $city = City::query()->create([
            'name' => 'Сочи',
            'slug' => 'sochi',
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
                    'open_booking_widget' => true,
                ],
            ],
            'mediums' => [
                [
                    'key' => 'medium-cpc',
                    'source_key' => 'source-google',
                    'medium' => 'cpc',
                    'medium_name' => 'Google CPC',
                    'phone_key' => 'phone-medium',
                    'open_booking_widget' => true,
                    'start_date' => null,
                    'end_date' => null,
                ],
            ],
        ]);

        $editorState = $service->getEditorState($city->fresh());

        $this->assertTrue((bool) data_get($editorState, 'sources.0.open_booking_widget'));
        $this->assertTrue((bool) data_get($editorState, 'mediums.0.open_booking_widget'));
        $this->assertNull(data_get($city->fresh()->utm_phones, '0.open_booking_widget'));
        $this->assertNull(data_get($city->fresh()->utm_phones, '0.medium.0.open_booking_widget'));
    }
}
