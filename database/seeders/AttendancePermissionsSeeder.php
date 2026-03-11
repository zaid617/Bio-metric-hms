<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AttendancePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define attendance-related permissions
        $permissions = [
            // Device permissions
            'view attendance devices',
            'manage attendance devices',
            'sync attendance',

            // Record permissions
            'view attendance records',
            'manage attendance records',

            // Payroll permissions
            'view payroll',
            'generate payroll',
            'manage payroll',
            'approve payroll',

            // Report permissions are already covered by 'view_reports' permission
        ];

        // Create permissions if they don't exist
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission],
                ['guard_name' => 'web']
            );
        }

        $this->command->info('Attendance permissions created successfully!');

        // Assign all attendance permissions to admin role
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'web')->first();

        if ($adminRole) {
            // Give permissions to admin (ADD, don't replace)
            $attendancePermissions = Permission::where('guard_name', 'web')
                ->where(function ($query) {
                    $query->where('name', 'like', '%attendance%')
                          ->orWhere('name', 'like', '%payroll%')
                          ->orWhere('name', 'like', '%sync%');
                })
                ->get();

            foreach ($attendancePermissions as $permission) {
                $adminRole->givePermissionTo($permission);
            }

            $this->command->info('Admin role updated with attendance permissions!');
        }

        // Optionally assign some permissions to manager role
        $managerRole = Role::where('name', 'manager')->where('guard_name', 'web')->first();

        if ($managerRole) {
            $managerPermissions = [
                'view attendance devices',
                'view attendance records',
                'view payroll',
            ];

            foreach ($managerPermissions as $permission) {
                $perm = Permission::where('name', $permission)->where('guard_name', 'web')->first();
                if ($perm) {
                    $managerRole->givePermissionTo($perm);
                }
            }

            $this->command->info('Manager role updated with view-only attendance permissions!');
        }
    }
}
