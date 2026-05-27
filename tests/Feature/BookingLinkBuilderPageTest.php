<?php

namespace Tests\Feature;

use App\Models\BookingWidgetBranchOrder;
use App\Models\City;
use App\Models\CityUtmCampaign;
use App\Models\CityUtmPhone;
use App\Models\CityUtmSource;
use App\Models\Doctor;
use App\Models\Staff;
use App\Filament\Pages\BookingLinkBuilder;
use App\Services\BookingLinkBuilderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BookingLinkBuilderPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_booking_link_builder_page_with_direct_permission(): void
    {
        config()->set('app.url', 'https://example.test');

        $staff = Staff::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'password',
        ]);
        Permission::query()->create([
            'name' => 'page_BookingLinkBuilder',
            'guard_name' => 'staff',
        ]);
        $staff->givePermissionTo('page_BookingLinkBuilder');

        $city = City::query()->create([
            'name' => 'Москва',
            'slug' => 'moskva',
            'is_default' => true,
            'active' => true,
        ]);
        $doctor = Doctor::query()->create([
            'uuid' => '00000000-0000-0000-0000-000000000001',
            'surname' => 'Иванов',
            'name' => 'Иван',
            'speciality' => 'Офтальмолог',
            'job_title' => 'Офтальмолог',
            'excerpt' => 'Описание',
            'bio' => 'Биография',
            'handle' => 'ivanov-ivan',
        ]);
        DB::table('city_doctor')->insert([
            'city_id' => $city->id,
            'doctor_id' => $doctor->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        BookingWidgetBranchOrder::query()->create([
            'city_id' => $city->id,
            'clinic_id' => 2,
            'clinic_name' => 'Клиника',
            'branch_id' => 501,
            'title' => 'Центральный',
        ]);
        $this->createUtmCampaign($city);

        $utmOptions = app(BookingLinkBuilderService::class)->getUtmOptions($city);
        $this->assertNotEmpty($utmOptions);
        $this->assertNotNull(app(BookingLinkBuilderService::class)->getUtmPayloadByKey($city, array_key_first($utmOptions)));

        $response = $this
            ->actingAs($staff, 'staff')
            ->get('/admin/booking-links');

        $response
            ->assertOk()
            ->assertSee('Конструктор ссылок записи')
            ->assertSee('Готовая ссылка')
            ->assertSee('booking_doctor_id')
            ->assertSee('00000000-0000-0000-0000-000000000001')
            ->assertSee('utm_source')
            ->assertSee('direct')
            ->assertSee('utm_medium')
            ->assertSee('cpc')
            ->assertSee('utm_campaign')
            ->assertSee('doctor');
    }

    public function test_admin_can_open_booking_link_builder_page_with_widget_settings_permission(): void
    {
        config()->set('app.url', 'https://example.test');

        $staff = Staff::query()->create([
            'name' => 'Admin',
            'email' => 'admin-widget@example.test',
            'password' => 'password',
        ]);
        Permission::query()->create([
            'name' => 'page_BookingWidgetSettings',
            'guard_name' => 'staff',
        ]);
        $staff->givePermissionTo('page_BookingWidgetSettings');

        $city = City::query()->create([
            'name' => 'Москва',
            'slug' => 'moskva',
            'is_default' => true,
            'active' => true,
        ]);
        $doctor = Doctor::query()->create([
            'uuid' => '00000000-0000-0000-0000-000000000002',
            'surname' => 'Петров',
            'name' => 'Пётр',
            'speciality' => 'Офтальмолог',
            'job_title' => 'Офтальмолог',
            'excerpt' => 'Описание',
            'bio' => 'Биография',
            'handle' => 'petrov-petr',
        ]);
        DB::table('city_doctor')->insert([
            'city_id' => $city->id,
            'doctor_id' => $doctor->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this
            ->actingAs($staff, 'staff')
            ->get('/admin/booking-links')
            ->assertOk()
            ->assertSee('Конструктор ссылок записи')
            ->assertSee('booking_doctor_id')
            ->assertSee('00000000-0000-0000-0000-000000000002');
    }

    public function test_generated_url_updates_when_form_state_changes(): void
    {
        config()->set('app.url', 'https://example.test');

        $staff = Staff::query()->create([
            'name' => 'Admin',
            'email' => 'admin-live@example.test',
            'password' => 'password',
        ]);
        Permission::query()->create([
            'name' => 'page_BookingLinkBuilder',
            'guard_name' => 'staff',
        ]);
        $staff->givePermissionTo('page_BookingLinkBuilder');

        $city = City::query()->create([
            'name' => 'Москва',
            'slug' => 'moskva',
            'is_default' => true,
            'active' => true,
        ]);
        $firstDoctor = Doctor::query()->create([
            'uuid' => '00000000-0000-0000-0000-000000000010',
            'surname' => 'Иванов',
            'name' => 'Иван',
            'speciality' => 'Офтальмолог',
            'job_title' => 'Офтальмолог',
            'excerpt' => 'Описание',
            'bio' => 'Биография',
            'handle' => 'ivanov-ivan-live',
        ]);
        $secondDoctor = Doctor::query()->create([
            'uuid' => '00000000-0000-0000-0000-000000000011',
            'surname' => 'Сидоров',
            'name' => 'Сидор',
            'speciality' => 'Офтальмолог',
            'job_title' => 'Офтальмолог',
            'excerpt' => 'Описание',
            'bio' => 'Биография',
            'handle' => 'sidorov-sidor-live',
        ]);
        foreach ([$firstDoctor, $secondDoctor] as $doctor) {
            DB::table('city_doctor')->insert([
                'city_id' => $city->id,
                'doctor_id' => $doctor->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        BookingWidgetBranchOrder::query()->create([
            'city_id' => $city->id,
            'clinic_id' => 2,
            'clinic_name' => 'Клиника',
            'branch_id' => 777,
            'title' => 'Центральный',
        ]);
        $this->createUtmCampaign($city);

        $this->actingAs($staff, 'staff');

        Livewire::test(BookingLinkBuilder::class)
            ->assertSee('booking_doctor_id=00000000-0000-0000-0000-000000000010', false)
            ->set('data.doctor_id', $secondDoctor->uuid)
            ->assertSee('booking_doctor_id=00000000-0000-0000-0000-000000000011', false)
            ->set('data.entry', 'branch')
            ->assertSee('booking_branch_id=777', false)
            ->set('data.utm_key', '')
            ->assertDontSee('utm_source', false);
    }

    private function createUtmCampaign(City $city): void
    {
        $phone = CityUtmPhone::query()->create([
            'city_id' => $city->id,
            'phone' => '+7 000 000-00-01',
        ]);
        $source = CityUtmSource::query()->create([
            'city_id' => $city->id,
            'source' => 'direct',
            'name' => 'Директ',
            'default_phone_id' => $phone->id,
            'open_booking_widget' => false,
            'is_organic' => false,
        ]);

        CityUtmCampaign::query()->create([
            'city_id' => $city->id,
            'source_id' => $source->id,
            'medium' => 'cpc',
            'medium_name' => 'CPC',
            'campaign' => 'doctor',
            'campaign_name' => 'Врач',
            'phone_id' => $phone->id,
            'open_booking_widget' => false,
            'cabinet' => null,
            'vk_app_enabled' => false,
            'is_organic' => false,
            'is_organic_overridden' => false,
            'started_at' => '2026-05-01 09:00:00',
        ]);
    }
}
