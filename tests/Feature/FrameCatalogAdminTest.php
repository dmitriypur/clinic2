<?php

namespace Tests\Feature;

use App\Filament\Forms\Components\CuratorUrlPicker;
use App\Filament\Resources\FrameAgeGroupResource;
use App\Filament\Resources\FrameBrandResource;
use App\Filament\Resources\FrameColorResource;
use App\Filament\Resources\FrameResource;
use App\Filament\Resources\FrameResource\Pages\CreateFrame;
use App\Models\Staff;
use App\Models\Frame;
use App\Models\FrameBrand;
use App\Models\FrameColor;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FrameCatalogAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $staff = Staff::query()->create([
            'name' => 'Frame catalog admin',
            'email' => 'frames@example.test',
            'password' => 'password',
        ]);

        foreach (['create_frame', 'view_any_frame'] as $permissionName) {
            Permission::findOrCreate($permissionName, 'staff');
        }

        $staff->givePermissionTo('create_frame', 'view_any_frame');
        $this->actingAs($staff, 'staff');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_catalog_resources_are_grouped_in_one_admin_navigation_section(): void
    {
        $this->assertSame('Каталог оправ', FrameResource::getNavigationGroup());
        $this->assertSame('Каталог оправ', FrameBrandResource::getNavigationGroup());
        $this->assertSame('Каталог оправ', FrameColorResource::getNavigationGroup());
        $this->assertSame('Каталог оправ', FrameAgeGroupResource::getNavigationGroup());
    }

    public function test_catalog_permissions_are_installed_for_staff_roles(): void
    {
        foreach ([
            'view_any_frame',
            'view_frame',
            'create_frame',
            'update_frame',
            'delete_frame',
            'delete_any_frame',
            'reorder_frame',
            'view_any_frame_brand',
            'view_frame_brand',
            'create_frame_brand',
            'update_frame_brand',
            'reorder_frame_brand',
            'view_any_frame_color',
            'view_frame_color',
            'create_frame_color',
            'update_frame_color',
            'reorder_frame_color',
            'view_any_frame_age_group',
            'view_frame_age_group',
            'create_frame_age_group',
            'update_frame_age_group',
            'reorder_frame_age_group',
        ] as $permissionName) {
            $this->assertDatabaseHas('permissions', [
                'name' => $permissionName,
                'guard_name' => 'staff',
            ]);
        }
    }

    public function test_frame_form_uses_relations_fixed_genders_and_curator_without_size_fields(): void
    {
        Livewire::test(CreateFrame::class)
            ->assertFormFieldExists(
                'brand_id',
                fn ($field): bool => $field instanceof Select && ! $field->isMultiple(),
            )
            ->assertFormFieldExists('model')
            ->assertFormFieldExists('description')
            ->assertFormFieldExists(
                'colors',
                fn ($field): bool => $field instanceof Select && $field->isMultiple(),
            )
            ->assertFormFieldExists(
                'ageGroups',
                fn ($field): bool => $field instanceof Select && $field->isMultiple(),
            )
            ->assertFormFieldExists(
                'genders',
                fn ($field): bool => $field instanceof Select
                    && $field->isMultiple()
                    && $field->getOptions() === [
                        'boy' => 'Для мальчиков',
                        'girl' => 'Для девочек',
                    ],
            )
            ->assertFormFieldExists(
                'curator_media_id',
                fn ($field): bool => $field instanceof CuratorUrlPicker,
            )
            ->assertFormFieldExists('sort_order')
            ->assertFormFieldExists('is_hit')
            ->assertFormFieldExists('is_school_choice')
            ->assertFormFieldExists('is_active')
            ->assertFormFieldDoesNotExist('size')
            ->assertFormFieldDoesNotExist('sizes')
            ->assertFormFieldDoesNotExist('payload.items');
    }

    public function test_frame_color_selector_renders_a_color_swatch_in_its_labels(): void
    {
        $color = FrameColor::query()->create([
            'name' => 'Розовый',
            'hex' => '#F83072',
        ]);

        Livewire::test(CreateFrame::class)
            ->assertFormFieldExists(
                'colors',
                fn ($field): bool => $field instanceof Select
                    && $field->isHtmlAllowed()
                    && str_contains($field->getOptionLabelFromRecord($color), 'background-color: #F83072')
                    && str_contains($field->getOptionLabelFromRecord($color), 'Розовый'),
            );
    }

    public function test_demo_role_cannot_mutate_frames_or_dictionaries_even_with_permissions(): void
    {
        foreach (['update_frame', 'update_frame_brand'] as $permissionName) {
            Permission::findOrCreate($permissionName, 'staff');
        }
        $demoRole = Role::findOrCreate('demo', 'staff');
        $demo = Staff::query()->create([
            'name' => 'Demo catalog user',
            'email' => 'demo-frames@example.test',
            'password' => 'password',
        ]);
        $demo->assignRole($demoRole);
        $demo->givePermissionTo('update_frame', 'update_frame_brand');
        $brand = FrameBrand::query()->create(['name' => 'Demo brand']);
        $frame = Frame::query()->create([
            'brand_id' => $brand->id,
            'model' => 'Demo model',
            'genders' => ['boy'],
        ]);

        $this->assertTrue(Gate::forUser($demo)->denies('update', $frame));
        $this->assertTrue(Gate::forUser($demo)->denies('update', $brand));
    }
}
