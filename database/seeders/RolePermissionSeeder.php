<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ──────────────── Step 0: Clear Spatie permission cache ────────────────
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ──────────────── Step 1: Web guard permissions ────────────────
        $webPermissions = [
            'view_dashboard',
            'view patients',
            'create patients',
            'edit patients',
            'delete patients',
            'view appointments',
            'create appointments',
            'edit appointments',
            'delete appointments',
            'view consultation',
            'view enrollment',
            'create enrollment',
            'edit enrollment',
            'delete enrollment',
            'view feedback',
            'view payments',
            'create payments',
            'view returns',
            'create returns',
            'view_reports',
            'manage_appointments',
            'manage_sessions',
            'manage_payments',
            'create_patients',
            'book_appointments',
            'view_schedule',
        ];

        foreach ($webPermissions as $perm) {
            Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => 'web',
            ]);
        }

        $this->command->info('Web permissions created ✅');

        // ──────────────── Step 2: Doctor guard permissions ────────────────
        $doctorPermissions = $webPermissions; // same permissions for doctor guard
        foreach ($doctorPermissions as $perm) {
            Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => 'doctor',
            ]);
        }

        $this->command->info('Doctor permissions created ✅');

        // ──────────────── Step 3: Roles creation ────────────────
        // Admin role (web guard)
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);
        $adminRole->syncPermissions(Permission::where('guard_name','web')->get());
        $this->command->info('Admin role created ✅');

        // Manager role
        $managerRole = Role::firstOrCreate([
            'name' => 'manager',
            'guard_name' => 'web',
        ]);
        $managerRole->syncPermissions(Permission::where('guard_name','web')->get());
        $this->command->info('Manager role created ✅');

        // Receptionist
        $receptionistRole = Role::firstOrCreate([
            'name' => 'receptionist',
            'guard_name' => 'web',
        ]);
        $receptionistRole->givePermissionTo([
            'view_dashboard',
            'view patients', 'create patients', 'edit patients', 'delete patients',
            'view appointments', 'create appointments', 'edit appointments', 'delete appointments',
            'view consultation',
            'view enrollment', 'create enrollment', 'edit enrollment', 'delete enrollment',
            'view enrollments', 'create enrollments', 'edit enrollments', 'delete enrollments',
            'view feedback',
            'view payments', 'create payments', 'view returns', 'create returns',
        ]);
        $this->command->info('Receptionist role created ✅');

        // Accountant
        $accountantRole = Role::firstOrCreate([
            'name' => 'accountant',
            'guard_name' => 'web',
        ]);
        $accountantRole->givePermissionTo([
            'view_dashboard',
            'manage_payments',
            'view payments',
            'create payments',
        ]);
        $this->command->info('Accountant role created ✅');

        // Pharmacist
        $pharmacistRole = Role::firstOrCreate([
            'name' => 'pharmacist',
            'guard_name' => 'web',
        ]);
        $pharmacistRole->givePermissionTo([
            'view_dashboard',
            'view patients',
        ]);
        $this->command->info('Pharmacist role created ✅');

        // Cashier
        $cashierRole = Role::firstOrCreate([
            'name' => 'cashier',
            'guard_name' => 'web',
        ]);
        $cashierRole->givePermissionTo([
            'manage_payments',
        ]);
        $this->command->info('Cashier role created ✅');

        // View-only admin
        $viewOnlyRole = Role::firstOrCreate([
            'name' => 'view-only-admin',
            'guard_name' => 'web',
        ]);
        $viewOnlyRole->givePermissionTo([
            'view_dashboard',
            'view_reports',
        ]);
        $this->command->info('View-only admin role created ✅');

        // Doctor role (doctor guard)
        $doctorRole = Role::firstOrCreate([
            'name' => 'doctor',
            'guard_name' => 'doctor',
        ]);
        $doctorRole->syncPermissions(Permission::where('guard_name','doctor')->get());
        $this->command->info('Doctor role created ✅');

        // Doctor role (web guard) - for login
$doctorWebRole = Role::firstOrCreate([
    'name' => 'doctor',
    'guard_name' => 'web',
]);

// Ensure all doctor permissions exist for web guard
foreach ($doctorPermissions as $perm) {
    Permission::firstOrCreate([
        'name' => $perm,
        'guard_name' => 'web',
    ]);
}

// Assign permissions to doctor web role
$doctorWebRole->syncPermissions($doctorPermissions);

$this->command->info('Doctor role for web guard created ✅');


        // ──────────────── Step 4: Assign Admin role to admin user ────────────────
        $adminUser = User::find(1); // replace with your admin user ID
        if($adminUser){
            $adminUser->syncRoles([$adminRole]);
            $this->command->info('Admin role assigned to user ID 1 ✅');
        }
    }
}
