<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * Safe to run on existing installs — uses givePermissionTo() instead of syncPermissions()
 * so it ADDS the new permissions to roles without resetting any existing assignments.
 */
class AddSessionPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $newPerms = ['sessions.edit', 'sessions.delete'];

        foreach ($newPerms as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $get = fn(string $name) => Permission::where('name', $name)->where('guard_name', 'web')->first();

        // sessions.edit → admin, manager, receptionist
        foreach (['admin', 'super-admin', 'manager', 'receptionist'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role && $get('sessions.edit')) {
                $role->givePermissionTo($get('sessions.edit'));
            }
        }

        // sessions.delete → admin, manager only
        foreach (['admin', 'super-admin', 'manager'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role && $get('sessions.delete')) {
                $role->givePermissionTo($get('sessions.delete'));
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('sessions.edit and sessions.delete created and assigned.');
    }
}
