<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Admin\BookingWidgetBranchesTable;
use App\Livewire\Admin\BookingWidgetDoctorsTable;
use App\Models\BookingWidgetBranchOrder;
use App\Models\City;
use App\Models\Doctor;
use App\Models\Staff;
use App\Services\BookingWidgetBranchSyncService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class BookingWidgetSettingsAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_authorized_staff_can_update_doctor_and_branch_orders_and_sync_branches(): void
    {
        $staff = $this->createStaffWithBookingWidgetPermission();
        [$city, $doctor, $branch] = $this->createBookingWidgetRecords();
        $this->actingAs($staff, 'staff');

        $syncService = $this->mock(BookingWidgetBranchSyncService::class);
        $syncService->shouldReceive('syncCity')->once()->with($city->id);

        Livewire::test(BookingWidgetDoctorsTable::class, ['cityId' => $city->id])
            ->call('updateTableColumnState', 'doctor_widget_sort_order', (string) $doctor->id, 25)
            ->assertOk();

        Livewire::test(BookingWidgetBranchesTable::class, ['cityId' => $city->id])
            ->assertSeeHtml('wire:init="syncBranchesIfNeeded"')
            ->call('updateTableColumnState', 'sort_order', (string) $branch->id, 35)
            ->call('syncBranchesIfNeeded')
            ->assertOk();

        $this->assertDatabaseHas('city_doctor', [
            'city_id' => $city->id,
            'doctor_id' => $doctor->id,
            'sort_order' => 25,
        ]);
        $this->assertSame(35, $branch->refresh()->sort_order);
    }

    public function test_staff_without_page_permission_cannot_mount_booking_widget_tables(): void
    {
        $staff = $this->createStaffWithBookingWidgetPermission(false);
        $city = $this->createCity('Москва');
        $this->actingAs($staff, 'staff');

        Livewire::test(BookingWidgetDoctorsTable::class, ['cityId' => $city->id])
            ->assertForbidden();

        Livewire::test(BookingWidgetBranchesTable::class, ['cityId' => $city->id])
            ->assertForbidden();
    }

    public function test_demo_staff_can_view_tables_but_cannot_change_orders_or_sync_branches(): void
    {
        $staff = $this->createStaffWithBookingWidgetPermission();
        Role::findOrCreate('demo', 'staff');
        $staff->assignRole('demo');
        [$city, $doctor, $branch] = $this->createBookingWidgetRecords();
        $this->actingAs($staff, 'staff');

        $syncService = $this->mock(BookingWidgetBranchSyncService::class);
        $syncService->shouldNotReceive('syncCity');

        Livewire::test(BookingWidgetDoctorsTable::class, ['cityId' => $city->id])
            ->assertOk()
            ->call('updateTableColumnState', 'doctor_widget_sort_order', (string) $doctor->id, 25)
            ->assertForbidden();

        Livewire::test(BookingWidgetBranchesTable::class, ['cityId' => $city->id])
            ->assertOk()
            ->assertDontSeeHtml('wire:init="syncBranchesIfNeeded"')
            ->call('updateTableColumnState', 'sort_order', (string) $branch->id, 35)
            ->assertForbidden();

        Livewire::test(BookingWidgetBranchesTable::class, ['cityId' => $city->id])
            ->call('syncBranchesIfNeeded')
            ->assertForbidden();

        $this->assertDatabaseHas('city_doctor', [
            'city_id' => $city->id,
            'doctor_id' => $doctor->id,
            'sort_order' => 10,
        ]);
        $this->assertSame(20, $branch->refresh()->sort_order);
    }

    public function test_revoked_permission_blocks_doctor_order_update_after_mount(): void
    {
        $staff = $this->createStaffWithBookingWidgetPermission();
        [$city, $doctor] = $this->createBookingWidgetRecords();
        $this->actingAs($staff, 'staff');

        $component = Livewire::test(BookingWidgetDoctorsTable::class, ['cityId' => $city->id]);
        $staff->revokePermissionTo('page_BookingWidgetSettings');

        $component
            ->call('updateTableColumnState', 'doctor_widget_sort_order', (string) $doctor->id, 25)
            ->assertForbidden();

        $this->assertDatabaseHas('city_doctor', [
            'city_id' => $city->id,
            'doctor_id' => $doctor->id,
            'sort_order' => 10,
        ]);
    }

    public function test_revoked_permission_blocks_branch_order_update_and_sync_after_mount(): void
    {
        $staff = $this->createStaffWithBookingWidgetPermission();
        [$city, , $branch] = $this->createBookingWidgetRecords();
        $this->actingAs($staff, 'staff');

        $syncService = $this->mock(BookingWidgetBranchSyncService::class);
        $syncService->shouldNotReceive('syncCity');

        $updateComponent = Livewire::test(BookingWidgetBranchesTable::class, ['cityId' => $city->id]);
        $syncComponent = Livewire::test(BookingWidgetBranchesTable::class, ['cityId' => $city->id]);
        $staff->revokePermissionTo('page_BookingWidgetSettings');

        $updateComponent
            ->call('updateTableColumnState', 'sort_order', (string) $branch->id, 35)
            ->assertForbidden();

        $syncComponent
            ->call('syncBranchesIfNeeded')
            ->assertForbidden();

        $this->assertSame(20, $branch->refresh()->sort_order);
    }

    public function test_city_id_cannot_be_tampered_with_after_doctor_table_mount(): void
    {
        $staff = $this->createStaffWithBookingWidgetPermission();
        [$city, $doctor] = $this->createBookingWidgetRecords();
        [$otherCity, $otherDoctor] = $this->createBookingWidgetRecords('Киров');
        $this->actingAs($staff, 'staff');

        try {
            Livewire::test(BookingWidgetDoctorsTable::class, ['cityId' => $city->id])
                ->set('cityId', $otherCity->id)
                ->call('updateTableColumnState', 'doctor_widget_sort_order', (string) $otherDoctor->id, 99);

            $this->fail('Changing the locked cityId must fail.');
        } catch (CannotUpdateLockedPropertyException) {
            $this->assertDatabaseHas('city_doctor', [
                'city_id' => $otherCity->id,
                'doctor_id' => $otherDoctor->id,
                'sort_order' => 10,
            ]);
            $this->assertDatabaseHas('city_doctor', [
                'city_id' => $city->id,
                'doctor_id' => $doctor->id,
                'sort_order' => 10,
            ]);
        }
    }

    public function test_city_id_cannot_be_tampered_with_after_branch_table_mount(): void
    {
        $staff = $this->createStaffWithBookingWidgetPermission();
        [$city, , $branch] = $this->createBookingWidgetRecords();
        [$otherCity, , $otherBranch] = $this->createBookingWidgetRecords('Киров');
        $this->actingAs($staff, 'staff');

        $syncService = $this->mock(BookingWidgetBranchSyncService::class);
        $syncService->shouldNotReceive('syncCity');

        try {
            Livewire::test(BookingWidgetBranchesTable::class, ['cityId' => $city->id])
                ->set('cityId', $otherCity->id)
                ->call('updateTableColumnState', 'sort_order', (string) $otherBranch->id, 99)
                ->call('syncBranchesIfNeeded');

            $this->fail('Changing the locked cityId must fail.');
        } catch (CannotUpdateLockedPropertyException) {
            $this->assertSame(20, $otherBranch->refresh()->sort_order);
            $this->assertSame(20, $branch->refresh()->sort_order);
        }
    }

    public function test_inactive_city_is_not_accepted_by_booking_widget_tables(): void
    {
        $staff = $this->createStaffWithBookingWidgetPermission();
        $this->createCity('Москва');
        $city = $this->createCity('Архивный город', false);
        $this->actingAs($staff, 'staff');
        $this->withoutExceptionHandling();

        try {
            Livewire::test(BookingWidgetDoctorsTable::class, ['cityId' => $city->id]);
            $this->fail('Inactive city must not be accepted by the doctors table.');
        } catch (NotFoundHttpException) {
            $this->addToAssertionCount(1);
        }

        try {
            Livewire::test(BookingWidgetBranchesTable::class, ['cityId' => $city->id]);
            $this->fail('Inactive city must not be accepted by the branches table.');
        } catch (NotFoundHttpException) {
            $this->addToAssertionCount(1);
        }
    }

    private function createStaffWithBookingWidgetPermission(bool $granted = true): Staff
    {
        $staff = Staff::query()->create([
            'name' => 'Booking manager',
            'email' => uniqid('booking-manager-', true).'@example.test',
            'password' => 'password',
        ]);

        Permission::findOrCreate('page_BookingWidgetSettings', 'staff');

        if ($granted) {
            $staff->givePermissionTo('page_BookingWidgetSettings');
        }

        return $staff;
    }

    /**
     * @return array{City, Doctor, BookingWidgetBranchOrder}
     */
    private function createBookingWidgetRecords(string $cityName = 'Москва'): array
    {
        $city = $this->createCity($cityName);
        $doctor = Doctor::query()->create([
            'uuid' => fake()->uuid(),
            'surname' => 'Иванов',
            'name' => 'Иван',
            'speciality' => 'Офтальмолог',
            'job_title' => 'Офтальмолог',
            'excerpt' => 'Описание',
            'bio' => 'Биография',
            'handle' => fake()->unique()->slug(),
        ]);

        DB::table('city_doctor')->insert([
            'city_id' => $city->id,
            'doctor_id' => $doctor->id,
            'sort_order' => 10,
            'clinic_sort_order' => 15,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $branch = BookingWidgetBranchOrder::query()->create([
            'city_id' => $city->id,
            'clinic_id' => 2,
            'clinic_name' => 'Клиника',
            'branch_id' => fake()->unique()->numberBetween(100, 100000),
            'title' => 'Филиал',
            'sort_order' => 20,
        ]);

        return [$city, $doctor, $branch];
    }

    private function createCity(string $name, bool $active = true): City
    {
        return City::query()->create([
            'name' => $name,
            'slug' => fake()->unique()->slug(),
            'is_default' => false,
            'active' => $active,
        ]);
    }
}
