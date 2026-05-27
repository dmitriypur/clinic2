<?php

namespace Tests\Unit\Services;

use App\Models\BookingWidgetBranchOrder;
use App\Models\Category;
use App\Models\City;
use App\Models\CityUtmCampaign;
use App\Models\CityUtmPhone;
use App\Models\CityUtmSource;
use App\Models\Doctor;
use App\Models\Page;
use App\Services\BookingLinkBuilderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookingLinkBuilderServiceTest extends TestCase
{
    use RefreshDatabase;

    private int $utmPhoneCounter = 0;

    public function test_builds_default_city_doctor_link_with_campaign_utm(): void
    {
        config()->set('app.url', 'https://example.test');

        $city = $this->createCity(isDefault: true);
        $doctor = $this->createDoctor('00000000-0000-0000-0000-000000000001');
        $this->attachDoctorToCity($doctor, $city);
        $utm = $this->createUtmCampaign($city, [
            'source' => 'yandex',
            'medium' => 'cpc',
            'campaign' => 'doctor_card',
        ]);

        $url = app(BookingLinkBuilderService::class)->buildUrl(
            city: $city,
            page: null,
            entry: 'doctor',
            targetId: $doctor->uuid,
            utm: $utm,
        );

        $this->assertSame(
            'https://example.test/?booking_doctor_id=00000000-0000-0000-0000-000000000001&utm_source=yandex&utm_medium=cpc&utm_campaign=doctor_card',
            $url,
        );
    }

    public function test_builds_non_default_city_branch_link_for_category_page_with_medium_utm(): void
    {
        config()->set('app.url', 'https://example.test');

        $city = $this->createCity(name: 'Киров', slug: 'kirov', isDefault: false);
        $category = Category::query()->create([
            'title' => 'Услуги',
            'handle' => 'services',
        ]);
        $page = Page::query()->create([
            'title' => 'Диагностика',
            'handle' => 'diagnostika',
            'active' => true,
            'category_id' => $category->id,
        ]);
        $page->cities()->attach($city->id);

        $utm = $this->createUtmCampaign($city, [
            'source' => 'vk',
            'medium' => 'banner',
            'campaign' => null,
        ]);

        $url = app(BookingLinkBuilderService::class)->buildUrl(
            city: $city,
            page: $page,
            entry: 'branch',
            targetId: '501',
            utm: $utm,
        );

        $this->assertSame(
            'https://example.test/kirov/services/diagnostika?booking_branch_id=501&utm_source=vk&utm_medium=banner',
            $url,
        );
    }

    public function test_utm_options_include_active_campaigns_and_exclude_archived_campaigns(): void
    {
        $city = $this->createCity();
        $active = $this->createUtmCampaign($city, [
            'source' => 'direct',
            'medium' => 'search',
            'campaign' => 'summer',
            'campaign_name' => 'Лето',
        ]);
        $archived = $this->createUtmCampaign($city, [
            'source' => 'old',
            'medium' => 'archive',
            'campaign' => 'closed',
            'archived_at' => '2026-05-01 10:00:00',
            'stopped_at' => '2026-05-01 10:00:00',
        ]);

        $options = app(BookingLinkBuilderService::class)->getUtmOptions($city);

        $this->assertArrayHasKey($active['key'], $options);
        $this->assertStringContainsString('direct / search / summer', $options[$active['key']]);
        $this->assertArrayNotHasKey($archived['key'], $options);
    }

    public function test_doctor_options_exclude_doctors_without_uuid(): void
    {
        $city = $this->createCity();
        $doctorWithUuid = $this->createDoctor('00000000-0000-0000-0000-000000000010', 'Иванов', 'Иван');
        $doctorWithoutUuid = $this->createDoctor(null, 'Петров', 'Пётр');
        $this->attachDoctorToCity($doctorWithUuid, $city);
        $this->attachDoctorToCity($doctorWithoutUuid, $city);

        $options = app(BookingLinkBuilderService::class)->getDoctorOptions($city);

        $this->assertSame([
            '00000000-0000-0000-0000-000000000010' => 'Иванов Иван',
        ], $options);
    }

    public function test_branch_options_return_booking_branch_ids_for_selected_city(): void
    {
        $city = $this->createCity();

        BookingWidgetBranchOrder::query()->create([
            'city_id' => $city->id,
            'clinic_id' => 2,
            'clinic_name' => 'Клиника',
            'branch_id' => 501,
            'title' => 'Центральный',
            'sort_order' => 1,
        ]);

        $options = app(BookingLinkBuilderService::class)->getBranchOptions($city);

        $this->assertSame([
            '501' => 'Клиника — Центральный',
        ], $options);
    }

    private function createCity(string $name = 'Москва', string $slug = 'moskva', bool $isDefault = true): City
    {
        return City::query()->create([
            'name' => $name,
            'slug' => $slug,
            'is_default' => $isDefault,
            'active' => true,
        ]);
    }

    private function createDoctor(?string $uuid, string $surname = 'Иванов', string $name = 'Иван'): Doctor
    {
        return Doctor::query()->create([
            'uuid' => $uuid,
            'surname' => $surname,
            'name' => $name,
            'speciality' => 'Офтальмолог',
            'job_title' => 'Офтальмолог',
            'excerpt' => 'Описание',
            'bio' => 'Биография',
            'handle' => strtolower($surname . '-' . $name),
        ]);
    }

    private function attachDoctorToCity(Doctor $doctor, City $city): void
    {
        DB::table('city_doctor')->insert([
            'city_id' => $city->id,
            'doctor_id' => $doctor->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array{key: string, source: string, medium: ?string, campaign: ?string}
     */
    private function createUtmCampaign(City $city, array $overrides = []): array
    {
        $source = CityUtmSource::query()->create([
            'city_id' => $city->id,
            'source' => $overrides['source'] ?? 'source',
            'name' => $overrides['source_name'] ?? null,
            'default_phone_id' => null,
            'open_booking_widget' => false,
            'is_organic' => false,
        ]);

        $this->utmPhoneCounter++;

        $phone = CityUtmPhone::query()->create([
            'city_id' => $city->id,
            'phone' => '+7 000 000-' . str_pad((string) $this->utmPhoneCounter, 2, '0', STR_PAD_LEFT),
        ]);

        $campaign = CityUtmCampaign::query()->create([
            'city_id' => $city->id,
            'source_id' => $source->id,
            'medium' => $overrides['medium'] ?? null,
            'medium_name' => $overrides['medium_name'] ?? null,
            'campaign' => $overrides['campaign'] ?? null,
            'campaign_name' => $overrides['campaign_name'] ?? null,
            'phone_id' => $phone->id,
            'open_booking_widget' => false,
            'cabinet' => null,
            'vk_app_enabled' => false,
            'is_organic' => false,
            'is_organic_overridden' => false,
            'started_at' => $overrides['started_at'] ?? '2026-05-01 09:00:00',
            'stopped_at' => $overrides['stopped_at'] ?? null,
            'archived_at' => $overrides['archived_at'] ?? null,
        ]);

        return [
            'key' => implode('|', [
                $source->source,
                $campaign->medium ?: '',
                $campaign->campaign ?: '',
            ]),
            'source' => $source->source,
            'medium' => $campaign->medium,
            'campaign' => $campaign->campaign,
        ];
    }
}
