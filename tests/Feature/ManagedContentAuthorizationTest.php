<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BlockType;
use App\Filament\Pages\ManageSeoSettings;
use App\Filament\Resources\ArticleImportResource;
use App\Filament\Resources\BlockResource\Pages\ListBlocks;
use App\Filament\Resources\PagePostResource\Pages\ListPagePosts;
use App\Models\ArticleImport;
use App\Models\Block;
use App\Models\Category;
use App\Models\City;
use App\Models\Doctor;
use App\Models\Element;
use App\Models\Page;
use App\Models\Review;
use App\Models\Staff;
use App\Settings\SeoSettings;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ManagedContentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_managed_content_policies_allow_staff_with_explicit_update_permissions(): void
    {
        $staff = $this->createStaffWithPermissions([
            'update_page',
            'update_block',
            'update_city',
            'update_category',
            'update_doctor',
            'update_element',
            'update_review',
        ]);

        $this->assertTrue(Gate::forUser($staff)->allows('update', $this->createPage()));
        $this->assertTrue(Gate::forUser($staff)->allows('update', $this->createBlock()));
        $this->assertTrue(Gate::forUser($staff)->allows('update', $this->createCity()));
        $this->assertTrue(Gate::forUser($staff)->allows('update', new Category));
        $this->assertTrue(Gate::forUser($staff)->allows('update', new Doctor));
        $this->assertTrue(Gate::forUser($staff)->allows('update', new Element));
        $this->assertTrue(Gate::forUser($staff)->allows('update', new Review));
    }

    public function test_managed_content_policies_deny_staff_without_update_permissions(): void
    {
        $staff = $this->createStaffWithPermissions([]);

        $this->assertFalse(Gate::forUser($staff)->allows('update', $this->createPage()));
        $this->assertFalse(Gate::forUser($staff)->allows('update', $this->createBlock()));
        $this->assertFalse(Gate::forUser($staff)->allows('update', $this->createCity()));
        $this->assertFalse(Gate::forUser($staff)->allows('update', new Category));
        $this->assertFalse(Gate::forUser($staff)->allows('update', new Doctor));
        $this->assertFalse(Gate::forUser($staff)->allows('update', new Element));
        $this->assertFalse(Gate::forUser($staff)->allows('update', new Review));
    }

    public function test_demo_role_cannot_update_managed_content_even_with_update_permissions(): void
    {
        $staff = $this->createStaffWithPermissions([
            'update_page',
            'update_block',
            'update_city',
            'update_category',
            'update_doctor',
            'update_element',
            'update_review',
        ]);
        Role::findOrCreate('demo', 'staff');
        $staff->assignRole('demo');

        $this->assertFalse(Gate::forUser($staff)->allows('update', $this->createPage()));
        $this->assertFalse(Gate::forUser($staff)->allows('update', $this->createBlock()));
        $this->assertFalse(Gate::forUser($staff)->allows('update', $this->createCity()));
        $this->assertFalse(Gate::forUser($staff)->allows('update', new Category));
        $this->assertFalse(Gate::forUser($staff)->allows('update', new Doctor));
        $this->assertFalse(Gate::forUser($staff)->allows('update', new Element));
        $this->assertFalse(Gate::forUser($staff)->allows('update', new Review));
    }

    public function test_seo_settings_page_requires_its_shield_permission(): void
    {
        $staff = $this->createStaffWithPermissions([]);
        $this->actingAs($staff, 'staff');

        $this->assertFalse(ManageSeoSettings::canAccess());

        Permission::findOrCreate('page_ManageSeoSettings', 'staff');
        $staff->givePermissionTo('page_ManageSeoSettings');

        $this->assertTrue(ManageSeoSettings::canAccess());
    }

    public function test_demo_role_cannot_call_seo_settings_save_directly(): void
    {
        $this->createSeoSettings();
        $staff = $this->createStaffWithPermissions(['page_ManageSeoSettings']);
        Role::findOrCreate('demo', 'staff');
        $staff->assignRole('demo');
        $this->actingAs($staff, 'staff');

        Livewire::test(ManageSeoSettings::class)
            ->set('data', $this->validSeoSettings([
                'header_scripts' => [
                    ['name' => 'Injected', 'value' => '<script>alert(1)</script>'],
                ],
            ]))
            ->call('save')
            ->assertForbidden();

        $this->assertSame([], app(SeoSettings::class)->header_scripts);
    }

    public function test_demo_role_cannot_call_save_and_regenerate_sitemap_directly(): void
    {
        Bus::fake();
        $this->createSeoSettings();
        $staff = $this->createStaffWithPermissions(['page_ManageSeoSettings']);
        Role::findOrCreate('demo', 'staff');
        $staff->assignRole('demo');
        $this->actingAs($staff, 'staff');

        Livewire::test(ManageSeoSettings::class)
            ->set('data', $this->validSeoSettings([
                'scripts' => [
                    ['name' => 'Injected', 'value' => '<script>alert(1)</script>'],
                ],
            ]))
            ->call('regenerateSitemap')
            ->assertForbidden();

        $this->assertSame([], app(SeoSettings::class)->scripts);
    }

    public function test_authorized_staff_can_save_seo_settings(): void
    {
        $this->createSeoSettings();
        $staff = $this->createStaffWithPermissions(['page_ManageSeoSettings']);
        $this->actingAs($staff, 'staff');

        Livewire::test(ManageSeoSettings::class)
            ->set('data', $this->validSeoSettings([
                'header_scripts' => [
                    ['name' => 'Analytics', 'value' => '<script src="/analytics.js"></script>'],
                ],
            ]))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(
            [['name' => 'Analytics', 'value' => '<script src="/analytics.js"></script>']],
            app(SeoSettings::class)->header_scripts,
        );
    }

    public function test_article_import_history_requires_page_and_block_creation_permissions(): void
    {
        $staff = $this->createStaffWithPermissions([]);
        $this->actingAs($staff, 'staff');

        $this->assertFalse(ArticleImportResource::canViewAny());

        Permission::findOrCreate('create_page', 'staff');
        Permission::findOrCreate('create_block', 'staff');
        $staff->givePermissionTo(['create_page', 'create_block']);

        $this->assertTrue(ArticleImportResource::canViewAny());
    }

    public function test_article_import_action_rejects_mounting_without_creation_permissions(): void
    {
        $staff = $this->createStaffWithPermissions(['view_any_page']);
        $this->actingAs($staff, 'staff');

        Livewire::test(ListPagePosts::class)
            ->assertActionHidden('importArticle')
            ->call('mountAction', 'importArticle')
            ->assertActionNotMounted('importArticle');

        $this->assertDatabaseCount(ArticleImport::class, 0);
    }

    public function test_article_import_action_is_available_with_page_and_block_creation_permissions(): void
    {
        $staff = $this->createStaffWithPermissions([
            'view_any_page',
            'create_page',
            'create_block',
        ]);
        $this->actingAs($staff, 'staff');

        Livewire::test(ListPagePosts::class)
            ->assertActionVisible('importArticle');
    }

    public function test_block_bulk_mutations_require_update_permission(): void
    {
        $staff = $this->createStaffWithPermissions(['view_any_block']);
        $block = $this->createBlock();
        $block->forceFill([
            'type' => BlockType::DOCTORS_ALT,
            'payload' => ['doctors' => [1]],
        ])->save();
        $this->actingAs($staff, 'staff');

        Livewire::test(ListBlocks::class)
            ->assertTableBulkActionHidden('excludeDoctorsAltDoctors')
            ->assertTableBulkActionHidden('clearDoctorsAltSelection')
            ->call('mountTableBulkAction', 'clearDoctorsAltSelection', [$block->getKey()])
            ->call('callMountedTableBulkAction');

        $this->assertSame([1], $block->refresh()->payload['doctors']);
    }

    public function test_block_bulk_mutations_are_available_with_update_permission(): void
    {
        $staff = $this->createStaffWithPermissions([
            'view_any_block',
            'update_block',
        ]);
        $this->actingAs($staff, 'staff');

        Livewire::test(ListBlocks::class)
            ->assertTableBulkActionVisible('excludeDoctorsAltDoctors')
            ->assertTableBulkActionVisible('clearDoctorsAltSelection');
    }

    public function test_block_bulk_mutations_remain_unavailable_to_demo_role(): void
    {
        $staff = $this->createStaffWithPermissions([
            'view_any_block',
            'update_block',
        ]);
        Role::findOrCreate('demo', 'staff');
        $staff->assignRole('demo');
        $this->actingAs($staff, 'staff');

        Livewire::test(ListBlocks::class)
            ->assertTableBulkActionHidden('excludeDoctorsAltDoctors')
            ->assertTableBulkActionHidden('clearDoctorsAltSelection');
    }

    private function createStaffWithPermissions(array $permissions): Staff
    {
        $staff = Staff::query()->create([
            'name' => 'Content manager',
            'email' => uniqid('content-', true).'@example.test',
            'password' => 'password',
        ]);

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'staff');
        }

        $staff->givePermissionTo($permissions);

        return $staff;
    }

    private function createPage(): Page
    {
        return Page::query()->create([
            'title' => 'Managed page',
            'handle' => uniqid('managed-page-', true),
            'active' => true,
            'body_html' => '<p>Managed HTML</p>',
            'header_scripts' => '<script src="/managed.js"></script>',
        ]);
    }

    private function createBlock(): Block
    {
        $page = $this->createPage();

        return Block::query()->create([
            'page_id' => $page->getKey(),
            'type' => BlockType::HTML_CODE,
            'title' => 'Managed HTML block',
            'body_html' => '<script src="/block.js"></script>',
        ]);
    }

    private function createCity(): City
    {
        return City::query()->create([
            'name' => uniqid('City ', true),
            'slug' => uniqid('city-', true),
            'active' => true,
        ]);
    }

    private function createSeoSettings(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('settings', function (Blueprint $table): void {
                $table->boolean('locked')->default(false)->change();
            });
        }

        $properties = [
            'robots_txt' => '',
            'scripts' => [],
            'header_scripts' => [],
            'ignore_sitemap_last_mode' => false,
            'logo_alt' => 'Clinic',
            'logo_title' => 'Clinic',
            'image_alt_template' => '{h1}',
            'image_title_template' => '{h1}',
        ];

        foreach ($properties as $name => $payload) {
            DB::table('settings')->updateOrInsert([
                'group' => 'seo',
                'name' => $name,
            ], [
                'locked' => false,
                'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function validSeoSettings(array $overrides = []): array
    {
        return array_replace([
            'robots_txt' => 'User-agent: *',
            'scripts' => [],
            'header_scripts' => [],
            'ignore_sitemap_last_mode' => false,
            'logo_alt' => 'Clinic',
            'logo_title' => 'Clinic',
            'image_alt_template' => '{h1}',
            'image_title_template' => '{h1}',
        ], $overrides);
    }
}
