<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ── All web-guard permissions (module.action format) ───────────────
        $webPermissions = [
            // Dashboard
            'dashboard.view',

            // Patients
            'patients.view',
            'patients.create',
            'patients.edit',
            'patients.delete',

            // Doctors
            'doctors.view',
            'doctors.create',
            'doctors.edit',
            'doctors.delete',

            // Appointments
            'appointments.view',
            'appointments.history',
            'appointments.print',
            'appointments.invoice',
            'appointments.sessions',
            'appointments.edit',
            'appointments.delete',
            'appointments.book',
            'appointments.book.edit',
            'appointments.book.delete',

            // Dr Consultations
            'consultations.checkup',
            'consultations.complete',

            // Enrollments
            'enrollments.complete',
            'enrollments.pending',

            // Sessions
            'sessions.ongoing',
            'sessions.completed',

            // Feedback
            'feedback.doctor',
            'feedback.patient',

            // Payments
            'payments.outstanding-invoices',
            'payments.completed-invoices',
            'payments.appointment-invoices',
            'payments.receivable',
            'payments.return',

            // Employees
            'employees.view',
            'employees.create',
            'employees.edit',
            'employees.delete',

            // Attendance
            'attendance.device.create',
            'attendance.device.edit',
            'attendance.device.delete',
            'attendance.records.view',
            'attendance.records.edit',
            'attendance.payroll.generate',
            'attendance.payroll.adjustments',
            'attendance.payroll.view',

            // Expenses
            'expenses.type.create',
            'expenses.type.edit',
            'expenses.type.delete',
            'expenses.create',
            'expenses.view',

            // General Settings
            'settings.branches.create',
            'settings.branches.edit',
            'settings.branches.delete',
            'settings.bank.create',
            'settings.bank.edit',
            'settings.bank.delete',
            'settings.branch-fee',
            'settings.payroll',

            // Users
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'users.roles.edit',

            // Reports
            'reports.bank-ledgers',
            'reports.branch-ledgers',
            'reports.all-transactions',

            // Payment Outstanding
            'payment-outstanding.view',
        ];

        foreach ($webPermissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // ── Doctor-guard permissions (subset relevant to doctors) ──────────
        $doctorPermissions = [
            'dashboard.view',
            'consultations.checkup',
            'consultations.complete',
            'enrollments.complete',
            'enrollments.pending',
            'sessions.ongoing',
            'sessions.completed',
            'feedback.doctor',
            'feedback.patient',
            'patients.view',
            'appointments.view',
            'appointments.book',
        ];

        foreach ($doctorPermissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'doctor']);
        }

        // ── Helper closures ────────────────────────────────────────────────
        $webPerm  = fn(string $name) => Permission::where('name', $name)->where('guard_name', 'web')->first();
        $allWeb   = fn(array $names) => collect($names)->map($webPerm)->filter()->all();
        $doctPerm = fn(string $name) => Permission::where('name', $name)->where('guard_name', 'doctor')->first();
        $allDoct  = fn(array $names) => collect($names)->map($doctPerm)->filter()->all();

        // ── Roles ──────────────────────────────────────────────────────────

        // super-admin — bypassed via Gate::before; still gets all perms for UI
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::where('guard_name', 'web')->get());

        // admin — everything except users.roles.edit
        $adminPerms = Permission::where('guard_name', 'web')
            ->where('name', '!=', 'users.roles.edit')
            ->get();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'])
            ->syncPermissions($adminPerms);

        // manager
        Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web'])
            ->syncPermissions($allWeb([
                'dashboard.view',
                'patients.view', 'patients.create', 'patients.edit', 'patients.delete',
                'doctors.view', 'doctors.create', 'doctors.edit', 'doctors.delete',
                'appointments.view', 'appointments.history', 'appointments.print',
                'appointments.invoice', 'appointments.sessions', 'appointments.edit',
                'appointments.delete', 'appointments.book', 'appointments.book.edit',
                'appointments.book.delete',
                'consultations.checkup', 'consultations.complete',
                'enrollments.complete', 'enrollments.pending',
                'sessions.ongoing', 'sessions.completed',
                'feedback.doctor', 'feedback.patient',
                'payments.outstanding-invoices', 'payments.completed-invoices',
                'payments.appointment-invoices', 'payments.receivable', 'payments.return',
                'employees.view', 'employees.create', 'employees.edit',
                'attendance.records.view', 'attendance.payroll.view',
                'expenses.view',
                'reports.bank-ledgers', 'reports.branch-ledgers', 'reports.all-transactions',
                'payment-outstanding.view',
            ]));

        // receptionist
        Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web'])
            ->syncPermissions($allWeb([
                'dashboard.view',
                'patients.view', 'patients.create', 'patients.edit',
                'appointments.view', 'appointments.history', 'appointments.print',
                'appointments.invoice', 'appointments.sessions', 'appointments.edit',
                'appointments.book', 'appointments.book.edit',
                'consultations.checkup',
                'enrollments.complete', 'enrollments.pending',
                'feedback.doctor', 'feedback.patient',
            ]));

        // doctor (web guard — for user creation / role assignment UI)
        Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web'])
            ->syncPermissions($allWeb([
                'dashboard.view',
                'consultations.checkup', 'consultations.complete',
                'enrollments.complete', 'enrollments.pending',
                'sessions.ongoing', 'sessions.completed',
                'feedback.doctor',
                'patients.view',
                'appointments.view', 'appointments.book',
            ]));

        // doctor (doctor guard)
        Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'doctor'])
            ->syncPermissions($allDoct([
                'dashboard.view',
                'consultations.checkup', 'consultations.complete',
                'enrollments.complete', 'enrollments.pending',
                'sessions.ongoing', 'sessions.completed',
                'feedback.doctor',
                'patients.view',
                'appointments.view', 'appointments.book',
            ]));

        // accountant
        Role::firstOrCreate(['name' => 'accountant', 'guard_name' => 'web'])
            ->syncPermissions($allWeb([
                'dashboard.view',
                'payments.outstanding-invoices', 'payments.completed-invoices',
                'payments.appointment-invoices', 'payments.receivable', 'payments.return',
                'expenses.type.create', 'expenses.type.edit', 'expenses.type.delete',
                'expenses.create', 'expenses.view',
                'reports.bank-ledgers', 'reports.branch-ledgers', 'reports.all-transactions',
                'payment-outstanding.view',
                'attendance.payroll.view',
            ]));

        // pharmacist
        Role::firstOrCreate(['name' => 'pharmacist', 'guard_name' => 'web'])
            ->syncPermissions($allWeb([
                'dashboard.view',
                'patients.view',
            ]));

        // cashier
        Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web'])
            ->syncPermissions($allWeb([
                'dashboard.view',
                'payments.outstanding-invoices', 'payments.completed-invoices',
                'payments.appointment-invoices', 'payments.receivable', 'payments.return',
                'payment-outstanding.view',
                'appointments.invoice',
            ]));

        // view-only-admin — all read-only permissions
        Role::firstOrCreate(['name' => 'view-only-admin', 'guard_name' => 'web'])
            ->syncPermissions($allWeb([
                'dashboard.view',
                'patients.view',
                'doctors.view',
                'appointments.view', 'appointments.history', 'appointments.print',
                'appointments.invoice',
                'consultations.checkup',
                'enrollments.pending',
                'sessions.ongoing', 'sessions.completed',
                'feedback.doctor', 'feedback.patient',
                'payments.outstanding-invoices', 'payments.completed-invoices',
                'payments.appointment-invoices', 'payments.receivable',
                'employees.view',
                'attendance.records.view', 'attendance.payroll.view',
                'expenses.view',
                'reports.bank-ledgers', 'reports.branch-ledgers', 'reports.all-transactions',
                'payment-outstanding.view',
            ]));

        // Remove legacy permissions that are no longer assigned to any role or user
        Permission::whereNotLike('name', '%.%')
            ->doesntHave('roles')
            ->doesntHave('users')
            ->delete();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
