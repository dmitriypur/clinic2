<?php

namespace Tests\Feature;

use App\Filament\Resources\DoctorResource\Pages\ListDoctors;
use App\Models\Doctor;
use App\Models\Staff;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DoctorImageOptimizationActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_optimization_action_is_visible_to_super_admin(): void
    {
        $this->actingAs($this->createSuperAdmin(), 'staff');

        Livewire::test(ListDoctors::class)
            ->assertActionVisible('optimize_doctor_images');
    }

    public function test_optimization_action_is_hidden_from_regular_staff(): void
    {
        $this->actingAs($this->createRegularStaff(), 'staff');

        Livewire::test(ListDoctors::class)
            ->assertActionHidden('optimize_doctor_images');
    }

    public function test_super_admin_can_regenerate_only_missing_main_doctor_images(): void
    {
        $this->actingAs($this->createSuperAdmin(), 'staff');

        Artisan::shouldReceive('call')
            ->once()
            ->with('media-library:regenerate', [
                'modelType' => Doctor::class,
                '--only' => ['main'],
                '--only-missing' => true,
                '--with-responsive-images' => true,
                '--force' => true,
            ])
            ->andReturn(0);

        Livewire::test(ListDoctors::class)
            ->callAction('optimize_doctor_images')
            ->assertHasNoActionErrors();
    }

    private function createSuperAdmin(): Staff
    {
        $staff = $this->createStaff('super-doctor-images@example.test');
        $role = Role::query()->create(['name' => 'super_admin', 'guard_name' => 'staff']);
        $permission = Permission::query()->create([
            'name' => 'view_any_doctor',
            'guard_name' => 'staff',
        ]);
        $staff->assignRole($role);
        $staff->givePermissionTo($permission);

        return $staff;
    }

    private function createRegularStaff(): Staff
    {
        $staff = $this->createStaff('doctor-images@example.test');
        $permission = Permission::query()->create([
            'name' => 'view_any_doctor',
            'guard_name' => 'staff',
        ]);
        $staff->givePermissionTo($permission);

        return $staff;
    }

    private function createStaff(string $email): Staff
    {
        return Staff::query()->create([
            'name' => 'Admin',
            'email' => $email,
            'password' => 'password',
        ]);
    }
}
