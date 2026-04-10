<?php

namespace Tests\Feature;

use App\Models\City;
use App\Services\BookingSiteDoctorsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class BookingDoctorsByDateApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_doctors_by_date_endpoint_enriches_cards_for_explicit_site_city(): void
    {
        $moscow = $this->createCity(
            name: 'Москва',
            slug: 'moskva',
            branches: [[
                'name' => 'Москва ВДНХ',
                'external_id' => 'branch-1',
                'address' => 'Москва, адрес из админки',
                'metro' => 'ВДНХ',
            ]]
        );

        $visibleDoctorUuid = '00000000-0000-0000-0000-000000000101';

        $siteDoctorsService = Mockery::mock(BookingSiteDoctorsService::class);
        $siteDoctorsService->shouldReceive('getPayloadByUuids')
            ->once()
            ->andReturn([
                'data' => [[
                    'uuid' => $visibleDoctorUuid,
                    'full_name' => 'Иванов Иван',
                    'speciality' => 'Офтальмолог',
                    'avatar_url' => null,
                    'avatar_image' => null,
                    'video_url' => null,
                    'excerpt' => null,
                    'receives_display' => 'Ведет прием с 3 лет',
                    'age_min_months' => 36,
                    'age_max_months' => null,
                    'receives_text' => null,
                    'extra' => [],
                ]],
                'meta' => [
                    'hidden_uuids' => ['00000000-0000-0000-0000-000000000102'],
                ],
            ]);
        $this->app->instance(BookingSiteDoctorsService::class, $siteDoctorsService);

        Http::fake([
            'https://adminzrenie.ru/api/v1/cities/10/doctors-by-date*' => Http::response([
                'data' => [
                    [
                        'id' => 'entry-1',
                        'doctor_id' => 1001,
                        'clinic_id' => 2,
                        'clinic_name' => 'Клиника 2',
                        'branch_id' => 501,
                        'branch_external_id' => 'branch-1',
                        'branch_name' => 'Филиал из API',
                        'branch_address' => 'Адрес из API',
                        'branch_metro' => 'Метро из API',
                        'date' => '2026-04-10',
                        'external_id' => $visibleDoctorUuid,
                        'available_slots' => 2,
                        'first_available_time' => '10:00',
                    ],
                    [
                        'id' => 'entry-2',
                        'doctor_id' => 1002,
                        'clinic_id' => 2,
                        'clinic_name' => 'Клиника 2',
                        'branch_id' => 501,
                        'branch_external_id' => 'branch-1',
                        'branch_name' => 'Филиал из API',
                        'branch_address' => 'Адрес из API',
                        'date' => '2026-04-10',
                        'external_id' => '00000000-0000-0000-0000-000000000102',
                        'available_slots' => 1,
                        'first_available_time' => '11:00',
                    ],
                ],
            ]),
        ]);

        $response = $this->getJson(
            '/api/booking/cities/10/doctors-by-date?site_city_id=' . $moscow->id
            . '&date=2026-04-10&birth_date=2020-04-10'
        );

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.uuid', strtolower($visibleDoctorUuid));
        $response->assertJsonPath('data.0.branch.address', 'Москва, адрес из админки');
        $response->assertJsonPath('data.0.branch.metro', 'ВДНХ');
        $response->assertJsonPath('data.0.branch.city', 'Москва');
        $response->assertJsonPath('data.0.branch.external_id', 'branch-1');
        $response->assertJsonPath('data.0.available_slots', 2);
        $response->assertJsonPath('data.0.first_available_time', '10:00');
    }

    public function test_booking_doctors_by_date_calendar_endpoint_counts_only_visible_site_doctors(): void
    {
        $city = $this->createCity(
            name: 'Москва',
            slug: 'moskva',
            branches: [[
                'name' => 'Москва ВДНХ',
                'external_id' => 'branch-1',
                'address' => 'Москва, адрес из админки',
                'metro' => 'ВДНХ',
            ]]
        );

        $visibleDoctorUuid = '00000000-0000-0000-0000-000000000201';

        $siteDoctorsService = Mockery::mock(BookingSiteDoctorsService::class);
        $siteDoctorsService->shouldReceive('getVisibleUuidsForCity')
            ->once()
            ->andReturn([$visibleDoctorUuid]);
        $this->app->instance(BookingSiteDoctorsService::class, $siteDoctorsService);

        Http::fake([
            'https://adminzrenie.ru/api/v1/cities/10/doctors-by-date/calendar*' => function ($request) use ($visibleDoctorUuid) {
                $this->assertSame($visibleDoctorUuid, data_get($request->data(), 'doctor_uuids'));
                return Http::response([
                    'data' => [
                        [
                            'date' => '2026-04-10',
                            'total_slots' => 3,
                            'available_slots' => 3,
                            'available_doctors' => 1,
                            'first_available_time' => '09:00',
                        ],
                        [
                            'date' => '2026-04-11',
                            'total_slots' => 0,
                            'available_slots' => 0,
                            'available_doctors' => 0,
                            'first_available_time' => null,
                        ],
                    ],
                ]);
            },
        ]);

        $response = $this->getJson(
            '/api/booking/cities/10/doctors-by-date/calendar?site_city_id=' . $city->id
            . '&date_from=2026-04-10&date_to=2026-04-11&birth_date=2020-04-10'
        );

        $response->assertOk();
        $response->assertJsonPath('data.0.date', '2026-04-10');
        $response->assertJsonPath('data.0.available_doctors', 1);
        $response->assertJsonPath('data.0.available_slots', 3);
        $response->assertJsonPath('data.0.first_available_time', '09:00');
        $response->assertJsonPath('data.1.date', '2026-04-11');
        $response->assertJsonPath('data.1.available_doctors', 0);
        $response->assertJsonPath('data.1.available_slots', 0);
        $response->assertJsonPath('data.1.first_available_time', null);
    }

    public function test_booking_doctor_branches_availability_endpoint_enriches_branch_cards_for_explicit_site_city(): void
    {
        $city = $this->createCity(
            name: 'Москва',
            slug: 'moskva',
            branches: [[
                'name' => 'Москва ВДНХ',
                'external_id' => 'branch-1',
                'address' => 'Москва, адрес из админки',
                'metro' => 'ВДНХ',
            ]]
        );

        Http::fake([
            'https://adminzrenie.ru/api/v1/doctors/1001/branches-availability*' => function ($request) {
                $this->assertSame('2026-04-10', data_get($request->data(), 'date'));
                $this->assertSame(2, data_get($request->data(), 'clinic_id'));
                $this->assertSame(10, data_get($request->data(), 'city_id'));

                return Http::response([
                    'data' => [[
                        'id' => 501,
                        'clinic_id' => 2,
                        'city_id' => 10,
                        'name' => 'Филиал из API',
                        'address' => 'Адрес из API',
                        'phone' => '+79990000000',
                        'external_id' => 'branch-1',
                        'integration_mode' => 'local',
                        'clinic_name' => 'Клиника 2',
                        'available_slots' => 3,
                        'first_available_time' => '09:00',
                        'has_available_slots' => true,
                    ]],
                    'meta' => [
                        'default_branch_id' => 501,
                    ],
                ]);
            },
        ]);

        $response = $this->getJson(
            '/api/booking/doctors/1001/branches-availability?site_city_id=' . $city->id
            . '&date=2026-04-10&clinic_id=2&city_id=10'
        );

        $response->assertOk();
        $response->assertJsonPath('data.0.id', 501);
        $response->assertJsonPath('data.0.address', 'Москва, адрес из админки');
        $response->assertJsonPath('data.0.metro', 'ВДНХ');
        $response->assertJsonPath('data.0.city', 'Москва');
        $response->assertJsonPath('data.0.external_id', 'branch-1');
        $response->assertJsonPath('data.0.available_slots', 3);
        $response->assertJsonPath('data.0.first_available_time', '09:00');
        $response->assertJsonPath('meta.default_branch_id', 501);
    }

    private function createCity(string $name = 'Москва', string $slug = 'moskva', array $branches = []): City
    {
        return City::query()->create([
            'name' => $name,
            'slug' => $slug,
            'is_default' => true,
            'active' => true,
            'branches' => $branches,
        ]);
    }

}
