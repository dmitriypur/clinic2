<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PageType;
use App\Filament\Resources\DoctorResource\Pages\ListDoctors;
use App\Filament\Resources\ReviewResource\Pages\ListReviews;
use App\Models\City;
use App\Models\Doctor;
use App\Models\Page;
use App\Models\Review;
use App\Models\Staff;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DoctorReviewActionAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_view_only_staff_cannot_mutate_doctors_through_table_columns_or_custom_bulk_actions(): void
    {
        $staff = $this->createStaffWithPermissions(['view_any_doctor']);
        $doctor = $this->createDoctor([
            'page_sort_order' => 10,
            'seo' => ['noindex' => true],
        ]);
        $this->actingAs($staff, 'staff');

        Livewire::test(ListDoctors::class)
            ->assertTableActionHidden('edit', $doctor)
            ->assertTableBulkActionHidden('activate')
            ->assertTableBulkActionHidden('deactivate')
            ->assertTableBulkActionHidden('delete')
            ->call('updateTableColumnState', 'page_sort_order', (string) $doctor->getKey(), 25)
            ->call('mountTableBulkAction', 'activate', [$doctor->getKey()])
            ->call('callMountedTableBulkAction')
            ->call('mountTableBulkAction', 'deactivate', [$doctor->getKey()])
            ->call('callMountedTableBulkAction');

        $doctor->refresh();

        $this->assertSame(10, $doctor->page_sort_order);
        $this->assertTrue((bool) data_get($doctor->seo, 'noindex'));
    }

    public function test_staff_with_update_permission_can_use_doctor_table_mutations(): void
    {
        $staff = $this->createStaffWithPermissions([
            'view_any_doctor',
            'update_doctor',
        ]);
        $doctor = $this->createDoctor([
            'page_sort_order' => 10,
            'seo' => ['noindex' => true],
        ]);
        $this->actingAs($staff, 'staff');

        Livewire::test(ListDoctors::class)
            ->assertTableActionVisible('edit', $doctor)
            ->assertTableBulkActionVisible('activate')
            ->assertTableBulkActionVisible('deactivate')
            ->assertTableBulkActionHidden('delete')
            ->call('updateTableColumnState', 'page_sort_order', (string) $doctor->getKey(), 25)
            ->callTableBulkAction('activate', [$doctor]);

        $doctor->refresh();

        $this->assertSame(25, $doctor->page_sort_order);
        $this->assertFalse((bool) data_get($doctor->seo, 'noindex'));

        Livewire::test(ListDoctors::class)
            ->callTableBulkAction('deactivate', [$doctor]);

        $this->assertTrue((bool) data_get($doctor->refresh()->seo, 'noindex'));
    }

    public function test_demo_staff_cannot_mutate_doctors_even_with_update_permission(): void
    {
        $staff = $this->createStaffWithPermissions([
            'view_any_doctor',
            'update_doctor',
        ]);
        Role::findOrCreate('demo', 'staff');
        $staff->assignRole('demo');
        $doctor = $this->createDoctor([
            'page_sort_order' => 10,
            'seo' => ['noindex' => true],
        ]);
        $this->actingAs($staff, 'staff');

        Livewire::test(ListDoctors::class)
            ->assertTableBulkActionHidden('activate')
            ->assertTableBulkActionHidden('deactivate')
            ->call('updateTableColumnState', 'page_sort_order', (string) $doctor->getKey(), 25)
            ->call('mountTableBulkAction', 'activate', [$doctor->getKey()])
            ->call('callMountedTableBulkAction');

        $doctor->refresh();

        $this->assertSame(10, $doctor->page_sort_order);
        $this->assertTrue((bool) data_get($doctor->seo, 'noindex'));
    }

    public function test_view_only_staff_cannot_mutate_reviews_through_table_columns_or_custom_bulk_actions(): void
    {
        $staff = $this->createStaffWithPermissions(['view_any_review']);
        $review = $this->createReview(['resource' => 1]);
        $page = $this->createServicePage();
        $currentCity = $this->createCity('Current city');
        $replacementCity = $this->createCity('Replacement city');
        $review->pages()->attach($page);
        $review->cities()->attach($currentCity);
        $this->actingAs($staff, 'staff');

        Livewire::test(ListReviews::class)
            ->assertTableActionHidden('edit', $review)
            ->assertTableActionHidden('replicate', $review)
            ->assertTableBulkActionHidden('detach_service')
            ->assertTableBulkActionHidden('assign_cities')
            ->assertTableBulkActionHidden('delete')
            ->call('updateTableColumnState', 'resource', (string) $review->getKey(), 2)
            ->call('mountTableBulkAction', 'detach_service', [$review->getKey()])
            ->set('mountedTableBulkActionData', ['page_id' => $page->getKey()])
            ->call('callMountedTableBulkAction')
            ->call('mountTableBulkAction', 'assign_cities', [$review->getKey()])
            ->set('mountedTableBulkActionData', [
                'city_ids' => [$replacementCity->getKey()],
                'mode' => 'replace',
            ])
            ->call('callMountedTableBulkAction');

        $this->assertSame(1, $review->refresh()->resource);
        $this->assertTrue($review->pages()->whereKey($page->getKey())->exists());
        $this->assertTrue($review->cities()->whereKey($currentCity->getKey())->exists());
        $this->assertFalse($review->cities()->whereKey($replacementCity->getKey())->exists());
    }

    public function test_staff_with_update_permission_can_use_review_table_mutations(): void
    {
        $staff = $this->createStaffWithPermissions([
            'view_any_review',
            'update_review',
        ]);
        $review = $this->createReview(['resource' => 1]);
        $page = $this->createServicePage();
        $currentCity = $this->createCity('Current city');
        $replacementCity = $this->createCity('Replacement city');
        $review->pages()->attach($page);
        $review->cities()->attach($currentCity);
        $this->actingAs($staff, 'staff');

        Livewire::test(ListReviews::class)
            ->assertTableActionVisible('edit', $review)
            ->assertTableActionHidden('replicate', $review)
            ->assertTableBulkActionVisible('detach_service')
            ->assertTableBulkActionVisible('assign_cities')
            ->assertTableBulkActionHidden('delete')
            ->call('updateTableColumnState', 'resource', (string) $review->getKey(), 2)
            ->callTableBulkAction('detach_service', [$review], [
                'page_id' => $page->getKey(),
            ])
            ->callTableBulkAction('assign_cities', [$review], [
                'city_ids' => [$replacementCity->getKey()],
                'mode' => 'replace',
            ]);

        $this->assertSame(2, $review->refresh()->resource);
        $this->assertFalse($review->pages()->whereKey($page->getKey())->exists());
        $this->assertFalse($review->cities()->whereKey($currentCity->getKey())->exists());
        $this->assertTrue($review->cities()->whereKey($replacementCity->getKey())->exists());
    }

    public function test_demo_staff_cannot_mutate_reviews_even_with_update_permission(): void
    {
        $staff = $this->createStaffWithPermissions([
            'view_any_review',
            'update_review',
        ]);
        Role::findOrCreate('demo', 'staff');
        $staff->assignRole('demo');
        $review = $this->createReview(['resource' => 1]);
        $page = $this->createServicePage();
        $review->pages()->attach($page);
        $this->actingAs($staff, 'staff');

        Livewire::test(ListReviews::class)
            ->assertTableBulkActionHidden('detach_service')
            ->assertTableBulkActionHidden('assign_cities')
            ->call('updateTableColumnState', 'resource', (string) $review->getKey(), 2)
            ->call('mountTableBulkAction', 'detach_service', [$review->getKey()])
            ->set('mountedTableBulkActionData', ['page_id' => $page->getKey()])
            ->call('callMountedTableBulkAction');

        $this->assertSame(1, $review->refresh()->resource);
        $this->assertTrue($review->pages()->whereKey($page->getKey())->exists());
    }

    public function test_standard_review_actions_keep_their_separate_policy_permissions(): void
    {
        $staff = $this->createStaffWithPermissions([
            'view_any_review',
            'replicate_review',
            'delete_any_review',
        ]);
        $review = $this->createReview();
        $this->actingAs($staff, 'staff');

        Livewire::test(ListReviews::class)
            ->assertTableActionHidden('edit', $review)
            ->assertTableActionVisible('replicate', $review)
            ->assertTableBulkActionVisible('delete')
            ->assertTableBulkActionHidden('detach_service')
            ->assertTableBulkActionHidden('assign_cities');
    }

    public function test_standard_doctor_actions_keep_their_separate_policy_permissions(): void
    {
        $staff = $this->createStaffWithPermissions([
            'view_any_doctor',
            'delete_any_doctor',
        ]);
        $doctor = $this->createDoctor();
        $this->actingAs($staff, 'staff');

        Livewire::test(ListDoctors::class)
            ->assertTableActionHidden('edit', $doctor)
            ->assertTableBulkActionVisible('delete')
            ->assertTableBulkActionHidden('activate')
            ->assertTableBulkActionHidden('deactivate');
    }

    private function createStaffWithPermissions(array $permissions): Staff
    {
        $staff = Staff::query()->create([
            'name' => 'Content manager',
            'email' => uniqid('doctor-review-', true).'@example.test',
            'password' => 'password',
        ]);

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'staff');
        }

        $staff->givePermissionTo($permissions);

        return $staff;
    }

    private function createDoctor(array $overrides = []): Doctor
    {
        return Doctor::query()->create(array_replace([
            'uuid' => (string) fake()->uuid(),
            'name' => 'Иван Иванович',
            'surname' => 'Иванов',
            'speciality' => 'Офтальмолог',
            'job_title' => 'Врач-офтальмолог',
            'excerpt' => 'Описание врача',
            'bio' => 'Биография врача',
            'handle' => uniqid('doctor-', true),
        ], $overrides));
    }

    private function createReview(array $overrides = []): Review
    {
        return Review::query()->forceCreate(array_replace([
            'service_uuid' => (string) fake()->uuid(),
            'name' => 'Пациент',
            'body_html' => '<p>Отзыв</p>',
            'rating' => 5,
            'resource' => 1,
        ], $overrides));
    }

    private function createServicePage(): Page
    {
        return Page::query()->create([
            'title' => 'Услуга',
            'handle' => uniqid('service-', true),
            'active' => true,
            'type' => PageType::Services,
        ]);
    }

    private function createCity(string $name): City
    {
        return City::query()->create([
            'name' => $name,
            'slug' => str($name.'-'.uniqid())->slug()->value(),
            'active' => true,
        ]);
    }
}
