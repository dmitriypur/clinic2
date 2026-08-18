<?php

namespace Tests\Feature;

use App\Enums\BlockType;
use App\Filament\Resources\BlockResource\Pages\CreateBlock;
use App\Models\Page;
use App\Models\Staff;
use Awcodes\Curator\Components\Forms\CuratorPicker;
use Awcodes\Curator\CuratorPlugin;
use Filament\Facades\Filament;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ExpertOpinionCuratorFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_expert_opinion_form_uses_curator_picker_and_hides_spatie_upload(): void
    {
        $staff = Staff::query()->create([
            'name' => 'Curator Admin',
            'email' => 'curator@example.test',
            'password' => 'password',
        ]);
        foreach (['create_block', 'view_any_block'] as $permission) {
            Permission::query()->create([
                'name' => $permission,
                'guard_name' => 'staff',
            ]);
        }
        $staff->givePermissionTo('create_block', 'view_any_block');
        $this->actingAs($staff, 'staff');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $page = Page::query()->create([
            'title' => 'Статья',
            'handle' => 'curator-form-test',
            'active' => true,
        ]);

        Livewire::test(CreateBlock::class)
            ->fillForm(['page_id' => $page->getKey()])
            ->set('data.type', BlockType::EXPERT_OPINION->value)
            ->assertFormFieldExists(
                'payload.curator_image_id',
                fn ($field): bool => $field instanceof CuratorPicker && ! $field->isHidden(),
            )
            ->assertFormFieldExists(
                'default',
                fn ($field): bool => $field instanceof SpatieMediaLibraryFileUpload && $field->isHidden(),
            );
    }

    public function test_admin_panel_registers_curator_plugin(): void
    {
        $this->assertInstanceOf(
            CuratorPlugin::class,
            Filament::getPanel('admin')->getPlugin('awcodes/curator'),
        );
    }
}
