<?php

namespace Tests\Feature;

use App\Models\BookingWidgetBranchOrder;
use App\Models\City;
use App\Models\Doctor;
use App\Services\BookingWidgetBranchSyncService;
use App\Services\BookingWidgetOrderingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookingWidgetSortingTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_order_map_returns_orders_for_selected_city(): void
    {
        $city = $this->createCity();

        $doctorA = $this->createDoctor('00000000-0000-0000-0000-000000000001', 'Иванов', 'Иван');
        $doctorB = $this->createDoctor('00000000-0000-0000-0000-000000000002', 'Петров', 'Пётр');
        $doctorC = $this->createDoctor('00000000-0000-0000-0000-000000000003', 'Сидоров', 'Сидор');

        DB::table('city_doctor')->insert([
            [
                'city_id' => $city->id,
                'doctor_id' => $doctorA->id,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'city_id' => $city->id,
                'doctor_id' => $doctorB->id,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'city_id' => $city->id,
                'doctor_id' => $doctorC->id,
                'sort_order' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->assertSame([
            strtolower($doctorA->uuid) => 1,
            strtolower($doctorB->uuid) => 2,
        ], app(BookingWidgetOrderingService::class)->getDoctorOrderMapForCity($city->id));
    }

    public function test_branch_order_map_returns_nested_orders_for_selected_city(): void
    {
        $city = $this->createCity();

        BookingWidgetBranchOrder::query()->create([
            'city_id' => $city->id,
            'clinic_id' => 2,
            'clinic_name' => 'Клиника 2',
            'branch_id' => 301,
            'title' => 'Филиал А',
            'sort_order' => 2,
        ]);

        BookingWidgetBranchOrder::query()->create([
            'city_id' => $city->id,
            'clinic_id' => 2,
            'clinic_name' => 'Клиника 2',
            'branch_id' => 302,
            'title' => 'Филиал Б',
            'sort_order' => 1,
        ]);

        $this->assertSame([
            '2' => [
                '302' => 1,
                '301' => 2,
            ],
        ], app(BookingWidgetOrderingService::class)->getBranchOrderMapForCity($city->id));
    }

    public function test_doctor_order_map_is_empty_without_city(): void
    {
        $this->assertSame([], app(BookingWidgetOrderingService::class)->getDoctorOrderMapForCity(null));
    }

    public function test_branch_sync_creates_local_branch_rows_for_city(): void
    {
        config()->set('zrenie-clinic.booking_allowed_clinic_ids', [2]);

        $city = $this->createCity(name: 'Москва');

        Http::fake([
            'https://adminzrenie.ru/api/v1/cities' => Http::response([
                'data' => [
                    ['id' => 10, 'name' => 'г. Москва'],
                ],
            ]),
            'https://adminzrenie.ru/api/v1/cities/10/clinics' => Http::response([
                'data' => [
                    ['id' => 1, 'name' => 'Игнорируемая клиника'],
                    ['id' => 2, 'name' => 'Нужная клиника'],
                ],
            ]),
            'https://adminzrenie.ru/api/v1/clinics/2/branches*' => Http::response([
                'data' => [
                    ['id' => 501, 'name' => 'Филиал 1'],
                    ['id' => 502, 'name' => 'Филиал 2'],
                ],
            ]),
        ]);

        app(BookingWidgetBranchSyncService::class)->syncCity($city->id);

        $this->assertDatabaseHas('booking_widget_branch_orders', [
            'city_id' => $city->id,
            'clinic_id' => 2,
            'branch_id' => 501,
            'clinic_name' => 'Нужная клиника',
            'title' => 'Филиал 1',
        ]);

        $this->assertDatabaseHas('booking_widget_branch_orders', [
            'city_id' => $city->id,
            'clinic_id' => 2,
            'branch_id' => 502,
            'clinic_name' => 'Нужная клиника',
            'title' => 'Филиал 2',
        ]);

        $this->assertDatabaseMissing('booking_widget_branch_orders', [
            'city_id' => $city->id,
            'clinic_id' => 1,
        ]);
    }

    private function createCity(string $name = 'Москва', string $slug = 'moskva'): City
    {
        return City::query()->create([
            'name' => $name,
            'slug' => $slug,
            'is_default' => true,
            'active' => true,
        ]);
    }

    private function createDoctor(string $uuid, string $surname, string $name): Doctor
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
}
