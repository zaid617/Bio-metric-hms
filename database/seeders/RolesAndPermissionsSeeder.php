<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // 🔹 Clear Spatie permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 🔹 Define all permissions (web guard)
        $permissions = [
            // Dashboard
            'view_dashboard',

            // Patients
            'view patients',
            'create patients',
            'edit patients',
            'delete patients',

            // Appointments
            'view appointments',
            'create appointments',
            'edit appointments',
            'delete appointments',

            // Consultation (view only)
            'view consultation',

            // Enrollment
            'view enrollment',
            'create enrollment',
            'edit enrollment',
            'delete enrollment',

            // Feedback (view only)
            'view feedback',

            // Payments & Returns
            'view payments',
            'create payments',
            'view returns',
            'create returns',

            // Reports
            'view_reports',

            // For compatibility / system use
            'manage_appointments',
            'manage_sessions',
            'manage_payments',
            'create_patients',
            'book_appointments',
            'view_schedule',
        ];

        // 🔹 Create web guard permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // 🔹 Add enrollment permissions also for web guard (fixes error)
        Permission::firstOrCreate(['name' => 'create enrollments', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view enrollments', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'edit enrollments', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete enrollments', 'guard_name' => 'web']);

        // 🔹 Admin – full access (web guard)
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'])
            ->givePermissionTo(Permission::where('guard_name', 'web')->get());

        // 🔹 Manager – full access (web guard)
        Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web'])
            ->givePermissionTo(Permission::where('guard_name', 'web')->get());

        // 🔹 Receptionist – restricted access (web guard)
        Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web'])
            ->givePermissionTo([
                'view_dashboard',

                // Patients
                'view patients',
                'create patients',
                'edit patients',
                'delete patients',

                // Appointments
                'view appointments',
                'create appointments',
                'edit appointments',
                'delete appointments',

                // Consultation (view only)
                'view consultation',

                // Enrollment
                'view enrollment',
                'create enrollment',
                'edit enrollment',
                'delete enrollment',
                'view enrollments',
                'create enrollments',
                'edit enrollments',
                'delete enrollments',

                // Feedback (view-only)
                'view feedback',

                // Payments & Returns
                'view payments',
                'create payments',
                'view returns',
                'create returns',
            ]);

        // 🔹 Doctor – appointments & sessions management (doctor guard)
        $doctorRole = Role::firstOrCreate(
            ['name' => 'doctor', 'guard_name' => 'doctor']
        );

        $doctorPermissions = [
            // Dashboard
            'view_dashboard',

            // Appointments
            'view appointments',
            'create appointments',
            'edit appointments',
            'delete appointments',
            'manage_appointments',

            // Sessions
            'manage_sessions',

            // Feedback
            'view feedback',

            // Consultation
            'view consultation',

            // Enrollment
            'view enrollments',
            'create enrollments',
            'edit enrollments',
            'delete enrollments',
        ];

        // Ensure each permission exists for doctor guard
        foreach ($doctorPermissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'doctor']);
        }

        // 🔹 Doctor – web guard (for create/edit user)
$doctorWebRole = Role::firstOrCreate(
    ['name' => 'doctor', 'guard_name' => 'web']
);

foreach ($doctorPermissions as $perm) {
    Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
}

$doctorWebRole->syncPermissions($doctorPermissions);


        // Assign permissions to doctor role
        $doctorRole->syncPermissions($doctorPermissions);

        // 🔹 Accountant – limited web guard permissions
        Role::firstOrCreate(['name' => 'accountant', 'guard_name' => 'web'])
            ->givePermissionTo([
                'view_dashboard',
                'manage_payments',
                'view payments',
                'create payments',
            ]);

        // 🔹 Pharmacist – limited web guard permissions
        Role::firstOrCreate(['name' => 'pharmacist', 'guard_name' => 'web'])
            ->givePermissionTo([
                'view_dashboard',
                'view patients',
            ]);

        // 🔹 Cashier
        Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web'])
            ->givePermissionTo([
                'manage_payments',
            ]);

        // 🔹 View-only Admin – reports and dashboard
        Role::firstOrCreate(['name' => 'view-only-admin', 'guard_name' => 'web'])
            ->givePermissionTo([
                'view_dashboard',
                'view_reports',
            ]);
    }
}
