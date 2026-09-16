<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Pages\PublicFileManager;
use App\Models\Staff;
use BezhanSalleh\FilamentShield\Resources\RoleResource\Pages\EditRole;
use BostjanOb\FilamentFileManager\Model\FileItem;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PublicFileManagerSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::disk('public')->put('existing.pdf', '%PDF-1.4 existing');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_view_permission_does_not_expose_file_mutations(): void
    {
        $staff = $this->createStaffWithPermissions(['page_PublicFileManager']);
        $this->actingAs($staff, 'staff');

        Livewire::test(PublicFileManager::class)
            ->assertTableActionHidden('create_folder')
            ->assertTableActionHidden('upload_file')
            ->assertTableBulkActionHidden('delete');
    }

    public function test_demo_role_remains_view_only_even_if_manage_permission_is_granted(): void
    {
        $staff = $this->createStaffWithPermissions([
            'page_PublicFileManager',
            'manage_PublicFileManager',
        ]);
        Role::findOrCreate('demo', 'staff');
        $staff->assignRole('demo');
        $this->actingAs($staff, 'staff');

        Livewire::test(PublicFileManager::class)
            ->assertTableActionHidden('create_folder')
            ->assertTableActionHidden('upload_file')
            ->assertTableBulkActionHidden('delete');
    }

    public function test_manage_permission_exposes_file_mutations_to_non_demo_staff(): void
    {
        $staff = $this->createStaffWithPermissions([
            'page_PublicFileManager',
            'manage_PublicFileManager',
        ]);
        $this->actingAs($staff, 'staff');

        Livewire::test(PublicFileManager::class)
            ->assertTableActionVisible('create_folder')
            ->assertTableActionVisible('upload_file')
            ->assertTableBulkActionVisible('delete');
    }

    public function test_view_only_staff_cannot_delete_a_file_through_a_direct_action_call(): void
    {
        $staff = $this->createStaffWithPermissions(['page_PublicFileManager']);
        $this->actingAs($staff, 'staff');
        $record = FileItem::queryForDiskAndPath('public')
            ->where('name', 'existing.pdf')
            ->firstOrFail();

        Livewire::test(PublicFileManager::class)
            ->call('mountTableAction', 'delete', (string) $record->getKey())
            ->call('callMountedTableAction');

        Storage::disk('public')->assertExists('existing.pdf');
    }

    public function test_public_file_manager_accepts_a_safe_image(): void
    {
        $this->actingAs($this->createFileManager(), 'staff');

        Livewire::test(PublicFileManager::class)
            ->callTableAction('upload_file', data: [
                'files' => [UploadedFile::fake()->image('safe-photo.jpg')],
            ])
            ->assertHasNoTableActionErrors();

        Storage::disk('public')->assertExists('safe-photo.jpg');
    }

    public function test_public_file_manager_accepts_inventoried_document_and_video_formats(): void
    {
        $this->actingAs($this->createFileManager(), 'staff');

        foreach ([
            UploadedFile::fake()->create('license.pdf', 10, 'application/pdf'),
            UploadedFile::fake()->create('clip.mp4', 10, 'video/mp4'),
            UploadedFile::fake()->create('photo.jfif', 10, 'image/jpeg'),
        ] as $file) {
            Livewire::test(PublicFileManager::class)
                ->callTableAction('upload_file', data: ['files' => [$file]])
                ->assertHasNoTableActionErrors();
        }

        Storage::disk('public')->assertExists('license.pdf');
        Storage::disk('public')->assertExists('clip.mp4');
        Storage::disk('public')->assertExists('photo.jfif');
    }

    public function test_permission_migration_assigns_management_only_to_operating_roles(): void
    {
        foreach (['super_admin', 'panel_user', 'demo', 'Editor', 'SEO', 'Utm'] as $role) {
            Role::findOrCreate($role, 'staff');
        }

        $migration = require database_path(
            'migrations/2026_09_16_120000_add_public_file_manager_manage_permission.php'
        );
        $migration->up();

        $this->assertTrue(Role::findByName('super_admin', 'staff')->hasPermissionTo('manage_PublicFileManager'));
        $this->assertTrue(Role::findByName('panel_user', 'staff')->hasPermissionTo('manage_PublicFileManager'));

        foreach (['demo', 'Editor', 'SEO', 'Utm'] as $role) {
            $this->assertFalse(Role::findByName($role, 'staff')->hasPermissionTo('manage_PublicFileManager'));
        }
    }

    public function test_shield_role_save_preserves_file_management_permission(): void
    {
        $manager = $this->createStaffWithPermissions([
            'view_any_role',
            'view_role',
            'update_role',
        ]);
        $this->actingAs($manager, 'staff');

        $role = Role::findOrCreate('panel_user', 'staff');
        Permission::findOrCreate('page_PublicFileManager', 'staff');
        Permission::findOrCreate('manage_PublicFileManager', 'staff');
        $role->givePermissionTo([
            'page_PublicFileManager',
            'manage_PublicFileManager',
        ]);

        Livewire::test(EditRole::class, ['record' => $role->getRouteKey()])
            ->fillForm([
                'name' => 'panel_user',
                'guard_name' => 'staff',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($role->refresh()->hasPermissionTo('manage_PublicFileManager'));
    }

    public function test_public_file_manager_rejects_svg_uploads(): void
    {
        $this->actingAs($this->createFileManager(), 'staff');

        Livewire::test(PublicFileManager::class)
            ->callTableAction('upload_file', data: [
                'files' => [UploadedFile::fake()->createWithContent(
                    'active.svg',
                    '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>',
                )],
            ])
            ->assertHasTableActionErrors(['files']);

        Storage::disk('public')->assertMissing('active.svg');
    }

    public function test_public_file_manager_checks_the_server_detected_mime_type(): void
    {
        $this->actingAs($this->createFileManager(), 'staff');

        Livewire::test(PublicFileManager::class)
            ->callTableAction('upload_file', data: [
                'files' => [UploadedFile::fake()
                    ->createWithContent('disguised.jpg', '<?php echo "unsafe";')
                    ->mimeType('text/x-php')],
            ])
            ->assertHasTableActionErrors(['files']);

        Storage::disk('public')->assertMissing('disguised.jpg');
    }

    public function test_public_file_manager_checks_the_original_extension(): void
    {
        $this->actingAs($this->createFileManager(), 'staff');

        Livewire::test(PublicFileManager::class)
            ->callTableAction('upload_file', data: [
                'files' => [UploadedFile::fake()
                    ->image('disguised.php')
                    ->mimeType('image/jpeg')],
            ])
            ->assertHasTableActionErrors(['files']);

        Storage::disk('public')->assertMissing('disguised.php');
    }

    private function createFileManager(): Staff
    {
        return $this->createStaffWithPermissions([
            'page_PublicFileManager',
            'manage_PublicFileManager',
        ]);
    }

    /**
     * @param  array<string>  $permissions
     */
    private function createStaffWithPermissions(array $permissions): Staff
    {
        $staff = Staff::query()->create([
            'name' => 'File manager',
            'email' => uniqid('files-', true).'@example.test',
            'password' => 'password',
        ]);

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'staff');
        }

        $staff->givePermissionTo($permissions);

        return $staff;
    }
}
