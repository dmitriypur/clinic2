<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Doctor;
use App\Services\BookingSiteDoctorsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class BookingWidgetCacheInvalidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        DB::connection()->getPdo()->sqliteCreateFunction(
            'JSON_UNQUOTE',
            static fn (mixed $value): mixed => $value,
            1
        );
    }

    public function test_site_doctor_payload_refreshes_immediately_after_doctor_update(): void
    {
        $doctor = $this->createDoctor([
            'price' => '2000',
            'price_child' => '1500',
            'exclude_from_branch_promo_price' => false,
        ]);
        $service = app(BookingSiteDoctorsService::class);

        $firstPayload = $service->getPayloadByUuids([$doctor->uuid]);

        $this->assertSame('2000', data_get($firstPayload, 'data.0.extra.price'));
        $this->assertFalse(data_get($firstPayload, 'data.0.extra.exclude_from_branch_promo_price'));

        $doctor->update([
            'extra' => [
                'price' => '2500',
                'price_child' => '1700',
                'exclude_from_branch_promo_price' => true,
            ],
        ]);

        $secondPayload = $service->getPayloadByUuids([$doctor->uuid]);

        $this->assertSame('2500', data_get($secondPayload, 'data.0.extra.price'));
        $this->assertTrue(data_get($secondPayload, 'data.0.extra.exclude_from_branch_promo_price'));
    }

    public function test_direct_doctor_payload_refreshes_immediately_after_doctor_update(): void
    {
        $city = $this->createCity();
        $doctor = $this->createDoctor([
            'price' => '2000',
            'price_child' => '1500',
            'exclude_from_branch_promo_price' => false,
        ]);

        Http::fake([
            'https://adminzrenie.ru/api/v1/cities/10/doctors*' => Http::response([
                'data' => [[
                    'id' => 1001,
                    'external_id' => $doctor->uuid,
                    'name' => 'Врач из API',
                ]],
            ]),
        ]);

        $url = '/api/booking/doctors/' . $doctor->uuid
            . '/launch?site_city_id=' . $city->id
            . '&booking_city_id=10&birth_date=2000-01-01';

        $this->getJson($url)
            ->assertOk()
            ->assertJsonPath('data.extra.price', '2000')
            ->assertJsonPath('data.extra.exclude_from_branch_promo_price', false);

        $doctor->update([
            'extra' => [
                'price' => '2500',
                'price_child' => '1700',
                'exclude_from_branch_promo_price' => true,
            ],
        ]);

        $this->getJson($url)
            ->assertOk()
            ->assertJsonPath('data.extra.price', '2500')
            ->assertJsonPath('data.extra.exclude_from_branch_promo_price', true);
    }

    public function test_date_flow_branch_price_refreshes_immediately_after_city_update(): void
    {
        $city = $this->createCity('1000');
        $doctor = $this->createDoctor([
            'price' => '2500',
            'price_child' => '1700',
        ]);

        Http::fake([
            'https://adminzrenie.ru/api/v1/cities/10/doctors-by-date*' => Http::response([
                'data' => [[
                    'id' => 'entry-1',
                    'doctor_id' => 1001,
                    'clinic_id' => 2,
                    'clinic_name' => 'Клиника 2',
                    'branch_id' => 501,
                    'branch_external_id' => 'branch-1',
                    'branch_name' => 'Филиал из API',
                    'branch_address' => 'Адрес из API',
                    'date' => '2026-07-15',
                    'external_id' => $doctor->uuid,
                    'available_slots' => 1,
                    'first_available_time' => '10:00',
                ]],
            ]),
        ]);

        $url = '/api/booking/cities/10/doctors-by-date?site_city_id=' . $city->id
            . '&date=2026-07-15&birth_date=2000-01-01';

        $this->getJson($url)
            ->assertOk()
            ->assertJsonPath('data.0.branch.price', '1000');

        $city->update([
            'branches' => [$this->branch('1400')],
        ]);

        $this->getJson($url)
            ->assertOk()
            ->assertJsonPath('data.0.branch.price', '1400');
    }

    private function createCity(string $adultPrice = '1000'): City
    {
        return City::query()->create([
            'name' => 'Москва',
            'slug' => 'moskva',
            'is_default' => true,
            'active' => true,
            'branches' => [$this->branch($adultPrice)],
        ]);
    }

    private function branch(string $adultPrice): array
    {
        return [
            'name' => 'Москва ВДНХ',
            'external_id' => 'branch-1',
            'address' => 'Москва, адрес из админки',
            'metro' => 'ВДНХ',
            'price' => $adultPrice,
            'price_child' => '800',
        ];
    }

    private function createDoctor(array $extra): Doctor
    {
        return Doctor::query()->create([
            'uuid' => (string) Str::uuid(),
            'surname' => 'Иванов',
            'name' => 'Иван',
            'speciality' => 'Офтальмолог',
            'job_title' => 'Офтальмолог',
            'excerpt' => 'Описание',
            'bio' => 'Биография',
            'handle' => 'ivanov-' . Str::lower(Str::random(8)),
            'extra' => $extra,
        ]);
    }
}
