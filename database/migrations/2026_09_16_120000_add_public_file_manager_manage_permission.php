<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISSION = 'manage_PublicFileManager';

    public function up(): void
    {
        $permission = Permission::findOrCreate(self::PERMISSION, 'staff');

        Role::query()
            ->with('permissions')
            ->where('guard_name', 'staff')
            ->whereIn('name', ['super_admin', 'panel_user'])
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($permission));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()
            ->where('guard_name', 'staff')
            ->where('name', self::PERMISSION)
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
