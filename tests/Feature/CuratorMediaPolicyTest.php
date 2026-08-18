<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BlockType;
use App\Models\Block;
use App\Models\CuratorMedia;
use App\Models\Page;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CuratorMediaPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_block_permissions_control_curator_media_access(): void
    {
        $staff = $this->createStaffWithPermissions([
            'view_any_block',
            'create_block',
            'update_block',
        ]);
        $media = $this->createMedia();

        $this->assertTrue(Gate::forUser($staff)->allows('viewAny', CuratorMedia::class));
        $this->assertTrue(Gate::forUser($staff)->allows('create', CuratorMedia::class));
        $this->assertTrue(Gate::forUser($staff)->allows('update', $media));
        $this->assertFalse(Gate::forUser($staff)->allows('delete', $media));
    }

    public function test_used_curator_media_cannot_be_deleted(): void
    {
        $staff = $this->createStaffWithPermissions(['delete_block']);
        $media = $this->createMedia();
        $this->assertTrue(Gate::forUser($staff)->allows('delete', $media));
        $page = Page::query()->create([
            'title' => 'Статья',
            'handle' => 'used-curator-media',
            'active' => true,
        ]);
        Block::query()->create([
            'page_id' => $page->getKey(),
            'type' => BlockType::EXPERT_OPINION,
            'title' => 'Мнение эксперта',
            'payload' => ['curator_image_id' => $media->getKey()],
        ]);

        $this->assertFalse(Gate::forUser($staff)->allows('delete', $media));
    }

    private function createStaffWithPermissions(array $permissions): Staff
    {
        $staff = Staff::query()->create([
            'name' => 'Curator Admin',
            'email' => uniqid().'@example.test',
            'password' => 'password',
        ]);

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'staff');
        }

        $staff->givePermissionTo($permissions);

        return $staff;
    }

    private function createMedia(): CuratorMedia
    {
        return CuratorMedia::query()->create([
            'disk' => 'public',
            'directory' => 'expert-opinions',
            'visibility' => 'public',
            'name' => 'expert',
            'path' => 'expert-opinions/expert.png',
            'type' => 'image/png',
            'ext' => 'png',
        ]);
    }
}
