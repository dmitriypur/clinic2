<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISSIONS = [
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
    ];

    public function up(): void
    {
        $permissions = collect(self::PERMISSIONS)
            ->map(fn (string $name): Permission => Permission::findOrCreate($name, 'staff'));

        Role::query()
            ->where('guard_name', 'staff')
            ->where('name', 'super_admin')
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($permissions));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()
            ->where('guard_name', 'staff')
            ->whereIn('name', self::PERMISSIONS)
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
