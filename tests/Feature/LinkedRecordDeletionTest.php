<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PageType;
use App\Filament\Resources\ReviewResource\Pages\EditReview;
use App\Filament\Resources\ReviewResource\Pages\ListReviews;
use App\Filament\Resources\TagResource\Pages\EditTag;
use App\Filament\Resources\TagResource\Pages\ListTags;
use App\Models\Page;
use App\Models\Review;
use App\Models\Staff;
use App\Models\Tag;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LinkedRecordDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_linked_review_edit_delete_is_rejected_with_notification(): void
    {
        $this->actingAs($this->staff(['view_any_review', 'view_review', 'update_review', 'delete_review']), 'staff');
        $review = $this->review();
        $review->pages()->attach($this->page());

        Livewire::test(EditReview::class, ['record' => $review->getKey()])
            ->callAction('delete')
            ->assertNotified('Нельзя удалить отзыв');

        $this->assertSame(1, Review::query()->count());
        $this->assertSame(1, DB::table('page_review')->count());
    }

    public function test_linked_tag_table_and_edit_delete_are_rejected_with_notification(): void
    {
        $this->actingAs($this->staff(['view_any_tag', 'view_tag', 'update_tag', 'delete_tag']), 'staff');
        $tag = $this->tag();
        $tag->pages()->attach($this->page());

        Livewire::test(ListTags::class)
            ->callTableAction('delete', $tag)
            ->assertNotified('Нельзя удалить тег');
        Livewire::test(EditTag::class, ['record' => $tag->getKey()])
            ->callAction('delete')
            ->assertNotified('Нельзя удалить тег');

        $this->assertSame(1, Tag::query()->count());
        $this->assertSame(1, DB::table('page_tag')->count());
    }

    public function test_mixed_review_bulk_selection_is_rejected_before_any_delete(): void
    {
        $this->actingAs($this->staff(['view_any_review', 'delete_any_review']), 'staff');
        $unlinked = $this->review();
        $linked = $this->review();
        $linked->pages()->attach($this->page());

        Livewire::test(ListReviews::class)
            ->callTableBulkAction('delete', [$unlinked, $linked])
            ->assertNotified('Нельзя удалить выбранные отзывы');

        $this->assertSame(2, Review::query()->count());
        $this->assertSame(1, DB::table('page_review')->count());
    }

    public function test_mixed_tag_bulk_selection_is_rejected_before_any_delete(): void
    {
        $this->actingAs($this->staff(['view_any_tag', 'delete_any_tag']), 'staff');
        $unlinked = $this->tag();
        $linked = $this->tag();
        $linked->pages()->attach($this->page());

        Livewire::test(ListTags::class)
            ->callTableBulkAction('delete', [$unlinked, $linked])
            ->assertNotified('Нельзя удалить выбранные теги');

        $this->assertSame(2, Tag::query()->count());
        $this->assertSame(1, DB::table('page_tag')->count());
    }

    public function test_unlinked_records_still_delete_through_standard_actions(): void
    {
        $this->actingAs($this->staff([
            'view_any_review', 'view_review', 'update_review', 'delete_review', 'delete_any_review',
            'view_any_tag', 'view_tag', 'update_tag', 'delete_tag', 'delete_any_tag',
        ]), 'staff');
        $reviewForEdit = $this->review();
        $reviewForBulk = $this->review();
        $tagForTable = $this->tag();
        $tagForEdit = $this->tag();
        $tagForBulk = $this->tag();

        Livewire::test(EditReview::class, ['record' => $reviewForEdit->getKey()])->callAction('delete');
        Livewire::test(ListReviews::class)->callTableBulkAction('delete', [$reviewForBulk]);
        Livewire::test(ListTags::class)->callTableAction('delete', $tagForTable);
        Livewire::test(EditTag::class, ['record' => $tagForEdit->getKey()])->callAction('delete');
        Livewire::test(ListTags::class)->callTableBulkAction('delete', [$tagForBulk]);

        $this->assertSame(0, Review::query()->count());
        $this->assertSame(0, Tag::query()->count());
    }

    public function test_view_only_staff_cannot_delete_records(): void
    {
        $review = $this->review();
        $tag = $this->tag();
        $viewOnly = $this->staff(['view_any_review', 'view_review', 'view_any_tag', 'view_tag']);
        $this->actingAs($viewOnly, 'staff');

        Livewire::test(ListReviews::class)->assertTableBulkActionHidden('delete');
        Livewire::test(ListTags::class)
            ->assertTableActionHidden('delete', $tag)
            ->assertTableBulkActionHidden('delete');

        Livewire::test(ListReviews::class)
            ->call('mountTableBulkAction', 'delete', [$review->getKey()])
            ->call('callMountedTableBulkAction');
        Livewire::test(ListTags::class)
            ->call('mountTableAction', 'delete', $tag->getKey())
            ->call('callMountedTableAction');

        $this->assertSame(1, Review::query()->count());
        $this->assertSame(1, Tag::query()->count());
    }

    public function test_demo_review_deletion_remains_denied_and_linked_tag_is_protected(): void
    {
        $review = $this->review();
        $tag = $this->tag();
        $tag->pages()->attach($this->page());

        $demo = $this->staff([
            'view_any_review', 'view_review', 'delete_review', 'delete_any_review',
            'view_any_tag', 'view_tag', 'delete_tag', 'delete_any_tag',
        ]);
        Role::findOrCreate('demo', 'staff');
        $demo->assignRole('demo');
        $this->actingAs($demo, 'staff');

        Livewire::test(ListReviews::class)->assertTableBulkActionHidden('delete');
        Livewire::test(ListTags::class)
            ->callTableAction('delete', $tag)
            ->assertNotified('Нельзя удалить тег');

        $this->assertSame(1, Review::query()->count());
        $this->assertSame(1, Tag::query()->count());
        $this->assertSame(1, DB::table('page_tag')->count());
    }

    private function staff(array $permissions): Staff
    {
        $staff = Staff::query()->create([
            'name' => 'Content manager',
            'email' => uniqid('linked-delete-', true).'@example.test',
            'password' => 'password',
        ]);

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'staff');
        }

        $staff->givePermissionTo($permissions);

        return $staff;
    }

    private function review(): Review
    {
        return Review::query()->forceCreate([
            'service_uuid' => (string) fake()->uuid(),
            'name' => 'Пациент',
            'body_html' => '<p>Отзыв</p>',
            'rating' => 5,
        ]);
    }

    private function tag(): Tag
    {
        return Tag::query()->create([
            'title' => 'Тег '.uniqid(),
            'handle' => uniqid('tag-'),
        ]);
    }

    private function page(): Page
    {
        return Page::query()->create([
            'title' => 'Услуга',
            'handle' => uniqid('service-'),
            'active' => true,
            'type' => PageType::Services,
        ]);
    }
}
