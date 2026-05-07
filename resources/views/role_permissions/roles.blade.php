@extends('layouts.app')

@section('title', 'Role Permissions')

@push('css')
<style>
/* ══════════════════════════════════════════════════════════════
   Role Permission Editor — Light Mode Redesign
══════════════════════════════════════════════════════════════ */

@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

/* ── Page wrapper ─────────────────────────────────────────── */
.rp-page {
    font-family: 'Plus Jakarta Sans', sans-serif;
}

/* ── Page header ──────────────────────────────────────────── */
.rp-header {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 20px 24px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    box-shadow: 0 1px 4px rgba(0,0,0,.05);
}
.rp-header-title {
    font-size: 1.25rem;
    font-weight: 800;
    color: #111827;
    letter-spacing: -.02em;
    margin: 0;
}
.rp-header-sub {
    font-size: .8rem;
    color: #9ca3af;
    margin-top: 2px;
    font-weight: 500;
}
.sa-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    color: #92400e;
    border: 1px solid #fbbf24;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: .75rem;
    font-weight: 700;
    letter-spacing: .04em;
}
.sa-badge .material-icons-outlined { font-size: .95rem; }

/* ── Toolbar ─────────────────────────────────────────────── */
.rp-toolbar {
    display: flex;
    align-items: center;
    gap: .6rem;
    flex-wrap: wrap;
    margin-bottom: 16px;
}
.rp-search {
    position: relative;
    flex: 1;
    max-width: 320px;
}
.rp-search input {
    padding: 9px 14px 9px 38px;
    border-radius: 10px;
    border: 1.5px solid #e5e7eb;
    background: #fff;
    color: #111827;
    font-size: .85rem;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 500;
    transition: border-color .15s, box-shadow .15s;
    width: 100%;
    outline: none;
}
.rp-search input::placeholder { color: #9ca3af; }
.rp-search input:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99,102,241,.1);
}
.rp-search .si {
    position: absolute;
    left: .7rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 1.05rem;
    color: #9ca3af;
    pointer-events: none;
}
.rp-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 8px 16px;
    border-radius: 10px;
    font-size: .8rem;
    font-weight: 700;
    font-family: 'Plus Jakarta Sans', sans-serif;
    cursor: pointer;
    border: 1.5px solid transparent;
    transition: all .15s;
    letter-spacing: .01em;
}
.rp-btn .material-icons-outlined { font-size: .95rem; }
.rp-btn-grant {
    background: #ecfdf5;
    color: #059669;
    border-color: #a7f3d0;
}
.rp-btn-grant:hover {
    background: #d1fae5;
    border-color: #059669;
    transform: translateY(-1px);
    box-shadow: 0 3px 10px rgba(5,150,105,.15);
}
.rp-btn-revoke {
    background: #fef2f2;
    color: #dc2626;
    border-color: #fecaca;
}
.rp-btn-revoke:hover {
    background: #fee2e2;
    border-color: #dc2626;
    transform: translateY(-1px);
    box-shadow: 0 3px 10px rgba(220,38,38,.15);
}

/* ── Role tab bar ────────────────────────────────────────── */
.role-tab-bar {
    display: flex;
    gap: .5rem;
    flex-wrap: wrap;
    margin-bottom: 20px;
    background: #fff;
    padding: 10px;
    border-radius: 14px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 4px rgba(0,0,0,.04);
}
.rtab {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 9px 16px;
    border-radius: 9px;
    cursor: pointer;
    font-size: .77rem;
    font-weight: 700;
    font-family: 'Plus Jakarta Sans', sans-serif;
    letter-spacing: .04em;
    text-transform: uppercase;
    border: 1.5px solid transparent;
    opacity: .55;
    user-select: none;
    transition: all .18s;
    background: transparent;
}
.rtab:hover {
    opacity: .85;
    transform: translateY(-1px);
}
.rtab.active {
    opacity: 1;
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(0,0,0,.12);
}
.rtab-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}
.rtab-count {
    border-radius: 10px;
    padding: 1px 8px;
    font-size: .68rem;
    font-weight: 700;
    line-height: 1.6;
    background: rgba(0,0,0,.1);
}

/* ── Role panel ──────────────────────────────────────────── */
.role-panel { display: none; }
.role-panel.active { display: block; }

/* ── Role summary strip ──────────────────────────────────── */
.role-strip {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    flex-wrap: wrap;
    padding: 18px 24px;
    border-radius: 14px;
    margin-bottom: 20px;
    border: 1.5px solid;
    background: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
}
.rs-label {
    font-size: .67rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    opacity: .5;
    margin-bottom: 2px;
}
.rs-name {
    font-size: 1.1rem;
    font-weight: 800;
    letter-spacing: -.01em;
}
.rs-big {
    font-size: 2.4rem;
    font-weight: 900;
    line-height: 1;
    letter-spacing: -.04em;
}
.rs-sub {
    font-size: .72rem;
    color: #9ca3af;
    margin-top: 2px;
    font-weight: 500;
}
.rs-divider {
    width: 1px;
    height: 48px;
    background: #e5e7eb;
    flex-shrink: 0;
}
.rs-progress-wrap { flex: 1; min-width: 180px; }
.rs-progress-label {
    display: flex;
    justify-content: space-between;
    font-size: .72rem;
    font-weight: 600;
    color: #6b7280;
    margin-bottom: 6px;
}
.rs-progress-track {
    height: 8px;
    border-radius: 4px;
    background: #f3f4f6;
    overflow: hidden;
}
.rs-progress-fill {
    height: 100%;
    border-radius: 4px;
    transition: width .5s ease;
}
.sa-bypass-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: .76rem;
    font-weight: 600;
    color: #92400e;
    padding: 7px 14px;
    border-radius: 20px;
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    border: 1px solid #fbbf24;
}
.sa-bypass-badge .material-icons-outlined { font-size: .95rem; }

/* ── Permission card grid ────────────────────────────────── */
.perm-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 14px;
}

/* ── Module card ─────────────────────────────────────────── */
.mod-card {
    border-radius: 14px;
    border: 1.5px solid #e5e7eb;
    background: #fff;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0,0,0,.05);
    transition: box-shadow .2s, transform .15s;
}
.mod-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,.09);
    transform: translateY(-1px);
}
.mod-card.hidden-card { display: none; }

.mod-card-head {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    border-bottom: 1.5px solid #f3f4f6;
    background: #fafafa;
}
.mod-card-icon {
    width: 34px;
    height: 34px;
    border-radius: 9px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}
.mod-card-icon .material-icons-outlined { font-size: 1.1rem; }
.mod-card-name {
    font-weight: 800;
    font-size: .76rem;
    letter-spacing: .06em;
    text-transform: uppercase;
    flex: 1;
    color: #374151;
}
.mod-card-badge {
    font-size: .68rem;
    font-weight: 700;
    padding: 2px 9px;
    border-radius: 20px;
    background: #f3f4f6;
    color: #6b7280;
    white-space: nowrap;
    border: 1px solid #e5e7eb;
}

/* Toggle-all pill */
.tog-all {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    cursor: pointer;
    font-size: .68rem;
    font-weight: 700;
    font-family: 'Plus Jakarta Sans', sans-serif;
    padding: 3px 9px;
    border-radius: 20px;
    border: 1.5px solid #e5e7eb;
    color: #6b7280;
    white-space: nowrap;
    transition: all .15s;
    background: #fff;
    flex-shrink: 0;
}
.tog-all:hover {
    border-color: #6366f1;
    color: #6366f1;
    background: #eef2ff;
}
.tog-all .material-icons-outlined { font-size: .85rem; }

/* ── Permission row ──────────────────────────────────────── */
.perm-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 15px;
    border-bottom: 1px solid #f9fafb;
    transition: background .1s;
}
.perm-item:last-child { border-bottom: none; }
.perm-item:hover { background: #fafafa; }
.perm-item.hidden-perm { display: none; }

.pi-info { flex: 1; min-width: 0; }
.pi-name {
    font-size: .82rem;
    font-weight: 600;
    color: #111827;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.pi-slug {
    font-size: .67rem;
    color: #9ca3af;
    font-family: 'Courier New', monospace;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-top: 1px;
}

/* ── Toggle switch (light mode) ──────────────────────────── */
.ts {
    position: relative;
    display: inline-block;
    width: 42px;
    height: 24px;
    flex-shrink: 0;
    cursor: pointer;
}
.ts input { position: absolute; opacity: 0; width: 0; height: 0; }
.ts-track {
    position: absolute;
    inset: 0;
    border-radius: 12px;
    background: #e5e7eb;
    transition: background .2s;
    border: 1.5px solid #d1d5db;
}
.ts-thumb {
    position: absolute;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    top: 3px;
    left: 3px;
    background: #9ca3af;
    transition: transform .2s, background .2s;
    box-shadow: 0 1px 4px rgba(0,0,0,.2);
}
.ts input:checked ~ .ts-track {
    border-color: transparent;
}
.ts input:checked ~ .ts-thumb {
    transform: translateX(18px);
    background: #fff;
    box-shadow: 0 1px 4px rgba(0,0,0,.25);
}
.ts.saving .ts-track { background: #f3f4f6 !important; border-color: #e5e7eb !important; }
.ts.saving .ts-thumb { background: #d1d5db; animation: pulse .8s ease infinite; }

/* Lock badge */
.sa-lk {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    font-size: .66rem;
    font-weight: 700;
    color: #92400e;
    padding: 3px 9px;
    border-radius: 20px;
    flex-shrink: 0;
    background: #fef3c7;
    border: 1px solid #fde68a;
    white-space: nowrap;
}
.sa-lk .material-icons-outlined { font-size: .78rem; }

/* ── Toast ───────────────────────────────────────────────── */
.rp-toast {
    position: fixed;
    bottom: 1.5rem;
    right: 1.5rem;
    z-index: 9999;
    min-width: 220px;
    border-radius: 12px;
    font-size: .84rem;
    font-weight: 600;
    font-family: 'Plus Jakarta Sans', sans-serif;
    box-shadow: 0 8px 28px rgba(0,0,0,.15);
    pointer-events: none;
}

/* ── No results ──────────────────────────────────────────── */
.no-results-box {
    text-align: center;
    padding: 3.5rem 1rem;
    color: #9ca3af;
    display: none;
}
.no-results-box .material-icons-outlined {
    font-size: 3.5rem;
    color: #d1d5db;
    display: block;
    margin-bottom: .75rem;
}
.no-results-box p {
    font-size: .9rem;
    font-weight: 600;
    margin: 0;
}

/* ── Responsive ──────────────────────────────────────────── */
@media (max-width: 640px) {
    .perm-grid { grid-template-columns: 1fr; }
    .rtab { padding: 7px 11px; font-size: .72rem; }
    .role-strip { gap: 1rem; padding: 14px 16px; }
    .rs-divider { display: none; }
}

@keyframes pulse {
    0%,100% { opacity: 1; }
    50%      { opacity: .35; }
}
</style>
@endpush

@section('content')
@php
use Spatie\Permission\Models\Role;

$webRoles = $roles->where('guard_name', 'web')->values();

$modules = [];
foreach ($permissions->where('guard_name', 'web') as $perm) {
    [$mod] = explode('.', $perm->name, 2);
    $modules[$mod][] = $perm;
}
ksort($modules);

$rolePermMap = [];
foreach ($webRoles as $role) {
    $rolePermMap[$role->id] = $role->permissions->keyBy('id')->map(fn() => true)->all();
}

$roleStyle = [
    'super-admin'    => ['bg'=>'#f59e0b','text'=>'#000'],
    'admin'          => ['bg'=>'#ef4444','text'=>'#fff'],
    'manager'        => ['bg'=>'#3b82f6','text'=>'#fff'],
    'receptionist'   => ['bg'=>'#06b6d4','text'=>'#000'],
    'view-only-admin'=> ['bg'=>'#6b7280','text'=>'#fff'],
    'accountant'     => ['bg'=>'#10b981','text'=>'#000'],
    'pharmacist'     => ['bg'=>'#8b5cf6','text'=>'#fff'],
    'cashier'        => ['bg'=>'#f97316','text'=>'#000'],
    'doctor'         => ['bg'=>'#ec4899','text'=>'#fff'],
];

$modIcon = [
    'dashboard'          => 'home',
    'patients'           => 'person',
    'doctors'            => 'medical_services',
    'appointments'       => 'assignment',
    'consultations'      => 'local_hospital',
    'enrollments'        => 'school',
    'sessions'           => 'event_note',
    'feedback'           => 'feedback',
    'payments'           => 'account_balance_wallet',
    'employees'          => 'badge',
    'attendance'         => 'fingerprint',
    'expenses'           => 'money_off',
    'settings'           => 'settings',
    'users'              => 'supervised_user_circle',
    'reports'            => 'bar_chart',
    'payment-outstanding'=> 'widgets',
];

$permLabel = [
    'dashboard.view'                => 'View Dashboard',
    'patients.view'                 => 'View All Patients',
    'patients.create'               => 'Add New Patient',
    'patients.edit'                 => 'Edit Patient',
    'patients.delete'               => 'Delete Patient',
    'doctors.view'                  => 'View All Doctors',
    'doctors.create'                => 'Add New Doctor',
    'doctors.edit'                  => 'Edit Doctor',
    'doctors.delete'                => 'Delete Doctor',
    'appointments.view'             => 'View All Appointments',
    'appointments.history'          => 'View History',
    'appointments.print'            => 'Print Appointment',
    'appointments.invoice'          => 'View / Generate Invoice',
    'appointments.sessions'         => 'View Linked Sessions',
    'appointments.edit'             => 'Edit Appointment',
    'appointments.delete'           => 'Delete Appointment',
    'appointments.book'             => 'Book New Appointment',
    'appointments.book.edit'        => 'Edit Booked Appointment',
    'appointments.book.delete'      => 'Delete Booked Appointment',
    'consultations.checkup'         => 'Dr Checkup',
    'consultations.complete'        => 'Complete Consultation',
    'enrollments.complete'          => 'Complete Enrollments',
    'enrollments.pending'           => 'Pending Enrollments',
    'sessions.ongoing'              => 'Ongoing Sessions',
    'sessions.completed'            => 'Completed Sessions',
    'sessions.edit'                 => 'Edit / Update Session',
    'sessions.delete'               => 'Delete Session',
    'feedback.doctor'               => 'Doctor Feedback',
    'feedback.patient'              => 'Patient Feedback',
    'payments.outstanding-invoices' => 'Outstanding Invoices',
    'payments.completed-invoices'   => 'Completed Invoices',
    'payments.appointment-invoices' => 'Appointment Invoices',
    'payments.receivable'           => 'Payment Receivable',
    'payments.return'               => 'Return Payments',
    'employees.view'                => 'View All Employees',
    'employees.create'              => 'Add New Employee',
    'employees.edit'                => 'Edit Employee',
    'employees.delete'              => 'Delete Employee',
    'attendance.device.create'      => 'Add Device',
    'attendance.device.edit'        => 'Edit Device',
    'attendance.device.delete'      => 'Delete Device',
    'attendance.records.view'       => 'View Attendance Records',
    'attendance.records.edit'       => 'Edit Attendance Records',
    'attendance.payroll.generate'   => 'Generate Payroll',
    'attendance.payroll.adjustments'=> 'Payroll Adjustments',
    'attendance.payroll.view'       => 'View Payroll',
    'expenses.type.create'          => 'Add Expense Type',
    'expenses.type.edit'            => 'Edit Expense Type',
    'expenses.type.delete'          => 'Delete Expense Type',
    'expenses.create'               => 'Create Expense',
    'expenses.view'                 => 'View Expenses',
    'settings.branches.create'      => 'Add Branch',
    'settings.branches.edit'        => 'Edit Branch',
    'settings.branches.delete'      => 'Delete Branch',
    'settings.bank.create'          => 'Add Bank Account',
    'settings.bank.edit'            => 'Edit Bank Account',
    'settings.bank.delete'          => 'Delete Bank Account',
    'settings.branch-fee'           => 'Branch Fee Settings',
    'settings.payroll'              => 'Payroll Settings',
    'users.view'                    => 'View All Users',
    'users.create'                  => 'Add New User',
    'users.edit'                    => 'Edit User',
    'users.delete'                  => 'Delete User',
    'users.roles.edit'              => 'Edit Role Permissions',
    'reports.bank-ledgers'          => 'Bank Ledgers',
    'reports.branch-ledgers'        => 'Branch Ledgers',
    'reports.all-transactions'      => 'All Transactions Report',
    'payment-outstanding.view'      => 'View Payment Outstanding',
];

$roleCounts = [];
foreach ($webRoles as $role) {
    $roleCounts[$role->id] = $role->permissions->filter(fn($p) => str_contains($p->name, '.'))->count();
}
$totalPerms = $permissions->where('guard_name','web')->count();
@endphp

<div class="rp-page">

{{-- ── Page header ─────────────────────────────────────────────── --}}
<div class="rp-header">
    <div>
        <h5 class="rp-header-title">Role Permissions</h5>
        <div class="rp-header-sub">{{ $totalPerms }} permissions &middot; {{ $webRoles->count() }} roles</div>
    </div>
    @if($isSuperAdmin)
    <span class="sa-badge">
        <span class="material-icons-outlined">shield</span>
        Super Admin
    </span>
    @endif
</div>

{{-- ── Toolbar ──────────────────────────────────────────────────── --}}
<div class="rp-toolbar">
    <div class="rp-search">
        <span class="si material-icons-outlined">search</span>
        <input type="text" id="permSearch" placeholder="Search permissions…">
    </div>
    <button class="rp-btn rp-btn-grant" id="btnGrantAll" title="Grant all to current role">
        <span class="material-icons-outlined">done_all</span>
        Grant All
    </button>
    <button class="rp-btn rp-btn-revoke" id="btnRevokeAll" title="Revoke all from current role">
        <span class="material-icons-outlined">remove_done</span>
        Revoke All
    </button>
</div>

{{-- ── Role tab bar ────────────────────────────────────────────── --}}
<div class="role-tab-bar" id="roleTabBar">
    @foreach($webRoles as $i => $role)
    @php
        $s   = $roleStyle[$role->name] ?? ['bg'=>'#555','text'=>'#fff'];
        $cnt = $roleCounts[$role->id];
        $isDark = $s['text'] === '#fff';
    @endphp
    <div class="rtab {{ $i === 0 ? 'active' : '' }}"
         data-role-tab="{{ $role->id }}"
         style="background:{{ $s['bg'] }}18; color:{{ $s['bg'] }}; border-color:{{ $s['bg'] }}40;
                {{ $i === 0 ? 'background:'.$s['bg'].'28; border-color:'.$s['bg'].';' : '' }}">
        <span class="rtab-dot" style="background:{{ $s['bg'] }};"></span>
        {{ role_display_name($role->name) }}
        <span class="rtab-count" style="background:{{ $s['bg'] }}20; color:{{ $s['bg'] }};"
              id="rtab-count-{{ $role->id }}">
            {{ $role->name === 'admin' ? '∞' : $cnt }}
        </span>
    </div>
    @endforeach
</div>

{{-- ── Role panels ─────────────────────────────────────────────── --}}
@foreach($webRoles as $i => $role)
@php
    $s    = $roleStyle[$role->name] ?? ['bg'=>'#555','text'=>'#fff'];
    $cnt  = $roleCounts[$role->id];
    $isLocked = ($role->name === 'admin');
    $pct  = ($totalPerms > 0 && !$isLocked) ? round($cnt / $totalPerms * 100) : 0;
@endphp

<div class="role-panel {{ $i === 0 ? 'active' : '' }}" id="panel-{{ $role->id }}" data-role="{{ $role->id }}">

    {{-- Role summary strip --}}
    <div class="role-strip" style="border-color:{{ $s['bg'] }}30; background: linear-gradient(135deg, {{ $s['bg'] }}08, #fff);">
        <div>
            <div class="rs-label" style="color:{{ $s['bg'] }};">Role</div>
            <div class="rs-name" style="color:{{ $s['bg'] }};">{{ role_display_name($role->name) }}</div>
        </div>
        <div class="rs-divider"></div>
        <div style="text-align:center; min-width:64px;">
            <div class="rs-big" style="color:{{ $s['bg'] }};">{{ $isLocked ? '∞' : $cnt }}</div>
            <div class="rs-sub">{{ $isLocked ? 'all perms' : 'of '.$totalPerms }}</div>
        </div>
        @if(!$isLocked)
        <div class="rs-divider"></div>
        <div class="rs-progress-wrap">
            <div class="rs-progress-label">
                <span>Permission Coverage</span>
                <span id="rs-pct-{{ $role->id }}" style="color:{{ $s['bg'] }}; font-weight:700;">{{ $pct }}%</span>
            </div>
            <div class="rs-progress-track">
                <div class="rs-progress-fill" id="rs-fill-{{ $role->id }}"
                     style="width:{{ $pct }}%; background:{{ $s['bg'] }};"></div>
            </div>
        </div>
        @else
        <div class="flex-grow-1">
            <span class="sa-bypass-badge">
                <span class="material-icons-outlined">lock</span>
                Gate::before bypass — skips all permission checks
            </span>
        </div>
        @endif
    </div>

    {{-- Permission grid --}}
    <div class="perm-grid" id="grid-{{ $role->id }}">

        @foreach($modules as $modName => $perms)
        @php
            $webPerms   = collect($perms)->where('guard_name','web')->values();
            $modTotal   = $webPerms->count();
            $modChecked = $isLocked ? $modTotal
                                : $webPerms->filter(fn($p) => isset($rolePermMap[$role->id][$p->id]))->count();
        @endphp
        @if($webPerms->isEmpty()) @continue @endif

        <div class="mod-card" data-mod="{{ $modName }}">
            {{-- Card header --}}
            <div class="mod-card-head">
                <div class="mod-card-icon" style="background:{{ $s['bg'] }}18;">
                    <span class="material-icons-outlined" style="color:{{ $s['bg'] }};">
                        {{ $modIcon[$modName] ?? 'circle' }}
                    </span>
                </div>
                <span class="mod-card-name">{{ ucfirst(str_replace('-', ' ', $modName)) }}</span>
                <span class="mod-card-badge" id="badge-{{ $role->id }}-{{ $modName }}"
                      style="background:{{ $s['bg'] }}12; color:{{ $s['bg'] }}; border-color:{{ $s['bg'] }}25;">
                    {{ $modChecked }}/{{ $modTotal }}
                </span>
                @if(!$isLocked)
                <span class="tog-all" data-role="{{ $role->id }}" data-mod="{{ $modName }}"
                      title="Toggle all in {{ ucfirst($modName) }}">
                    <span class="material-icons-outlined">
                        {{ $modChecked === $modTotal ? 'toggle_on' : 'toggle_off' }}
                    </span>
                    All
                </span>
                @endif
            </div>

            {{-- Permission rows --}}
            @foreach($webPerms as $perm)
            @php $checked = $isLocked || isset($rolePermMap[$role->id][$perm->id]); @endphp
            <div class="perm-item"
                 data-perm="{{ strtolower($perm->name) }}"
                 data-label="{{ strtolower($permLabel[$perm->name] ?? $perm->name) }}">
                <div class="pi-info">
                    <div class="pi-name">{{ $permLabel[$perm->name] ?? $perm->name }}</div>
                    <div class="pi-slug">{{ $perm->name }}</div>
                </div>
                @if($isLocked)
                <span class="sa-lk">
                    <span class="material-icons-outlined">lock</span>CEO Access
                </span>
                @else
                <label class="ts" title="{{ $role->name }}: {{ $permLabel[$perm->name] ?? $perm->name }}">
                    <input type="checkbox" class="perm-toggle"
                           {{ $checked ? 'checked' : '' }}
                           data-role="{{ $role->id }}"
                           data-permission="{{ $perm->name }}"
                           data-mod="{{ $modName }}"
                           data-color="{{ $s['bg'] }}">
                    <span class="ts-track"></span>
                    <span class="ts-thumb"></span>
                </label>
                @endif
            </div>
            @endforeach
        </div>
        @endforeach

    </div>{{-- /.perm-grid --}}

    <div class="no-results-box" id="noResults-{{ $role->id }}">
        <span class="material-icons-outlined">search_off</span>
        <p>No permissions match your search.</p>
    </div>

</div>{{-- /.role-panel --}}
@endforeach

{{-- Toast --}}
<div id="rpToast" class="rp-toast align-items-center border-0 toast" role="alert">
    <div class="d-flex align-items-center px-3 py-2 gap-2">
        <span id="rpToastIcon" class="material-icons-outlined" style="font-size:1.2rem;"></span>
        <span id="rpToastMsg"></span>
    </div>
</div>

</div>{{-- /.rp-page --}}
@endsection

@push('script')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
(function () {
'use strict';

const CSRF   = document.querySelector('meta[name=csrf-token]').content;
const UPDATE = @json(route('role.permissions.update'));

/* ── Active role ────────────────────────────────────────────────── */
let activeRoleId = document.querySelector('.rtab.active')?.dataset.roleTab ?? null;

/* ── Toast ─────────────────────────────────────────────────────── */
const toastEl  = document.getElementById('rpToast');
const toastMsg = document.getElementById('rpToastMsg');
const toastIco = document.getElementById('rpToastIcon');
let   toastTimer;

function showToast(msg, ok) {
    clearTimeout(toastTimer);
    toastMsg.textContent = msg;
    toastIco.textContent = ok ? 'check_circle' : 'error';
    toastEl.style.background = ok ? '#10b981' : '#ef4444';
    toastEl.style.color      = ok ? '#fff'     : '#fff';
    toastEl.classList.add('show');
    toastTimer = setTimeout(() => toastEl.classList.remove('show'), 2600);
}

/* ── Toggle track color ─────────────────────────────────────────── */
function syncColor(input) {
    const track = input.nextElementSibling; // .ts-track
    track.style.background = input.checked ? input.dataset.color : '';
    track.style.borderColor = input.checked ? input.dataset.color : '';
}
document.querySelectorAll('.perm-toggle').forEach(syncColor);

/* ── AJAX save ──────────────────────────────────────────────────── */
function savePermission(input, enable) {
    const label = input.closest('.ts');
    label.classList.add('saving');
    input.disabled = true;

    axios.post(UPDATE, {
        role_id:         input.dataset.role,
        permission_name: input.dataset.permission,
        has_permission:  enable ? 1 : 0,
    }, { headers: { 'X-CSRF-TOKEN': CSRF } })
    .then(() => {
        input.checked = enable;
        syncColor(input);
        showToast((enable ? 'Granted: ' : 'Revoked: ') + input.dataset.permission, true);
        updateModuleBadge(input.dataset.role, input.dataset.mod);
        updateRoleStrip(input.dataset.role);
    })
    .catch(err => {
        input.checked = !enable; // revert
        syncColor(input);
        showToast(err.response?.data?.message || 'Save failed', false);
    })
    .finally(() => {
        label.classList.remove('saving');
        input.disabled = false;
    });
}

/* ── Individual toggle ──────────────────────────────────────────── */
document.querySelectorAll('.perm-toggle').forEach(input => {
    input.addEventListener('change', function () {
        savePermission(this, this.checked);
    });
});

/* ── Module badge & tog-all icon update ─────────────────────────── */
function updateModuleBadge(roleId, modName) {
    const grid = document.getElementById('grid-' + roleId);
    if (!grid) return;
    const card = grid.querySelector('.mod-card[data-mod="' + modName + '"]');
    if (!card) return;

    const inputs  = [...card.querySelectorAll('.perm-toggle')];
    const checked = inputs.filter(i => i.checked).length;
    const total   = inputs.length;

    const badge = document.getElementById('badge-' + roleId + '-' + modName);
    if (badge) badge.textContent = checked + '/' + total;

    const togIcon = card.querySelector('.tog-all .material-icons-outlined');
    if (togIcon) togIcon.textContent = (checked === total && total > 0) ? 'toggle_on' : 'toggle_off';
}

/* ── Role strip update (progress bar + tab count) ───────────────── */
function updateRoleStrip(roleId) {
    const panel  = document.getElementById('panel-' + roleId);
    if (!panel) return;
    const inputs  = [...panel.querySelectorAll('.perm-toggle')];
    const checked = inputs.filter(i => i.checked).length;
    const total   = inputs.length;
    const pct     = total > 0 ? Math.round(checked / total * 100) : 0;

    const fill  = document.getElementById('rs-fill-' + roleId);
    const pctEl = document.getElementById('rs-pct-'  + roleId);
    if (fill)  fill.style.width    = pct + '%';
    if (pctEl) pctEl.textContent   = pct + '%';

    const tabCount = document.getElementById('rtab-count-' + roleId);
    if (tabCount && tabCount.textContent !== '∞') tabCount.textContent = checked;
}

/* ── Toggle All (module) ────────────────────────────────────────── */
document.querySelectorAll('.tog-all').forEach(pill => {
    pill.addEventListener('click', async function () {
        const roleId  = this.dataset.role;
        const modName = this.dataset.mod;
        const grid    = document.getElementById('grid-' + roleId);
        const card    = grid?.querySelector('.mod-card[data-mod="' + modName + '"]');
        if (!card) return;

        const inputs      = [...card.querySelectorAll('.perm-toggle')];
        const anyUnchecked = inputs.some(i => !i.checked);
        const enable       = anyUnchecked;

        for (const input of inputs) {
            if (input.disabled) continue;
            const needsChange = enable ? !input.checked : input.checked;
            if (!needsChange) continue;

            await new Promise(resolve => {
                const label = input.closest('.ts');
                label.classList.add('saving');
                input.disabled = true;

                axios.post(UPDATE, {
                    role_id:         roleId,
                    permission_name: input.dataset.permission,
                    has_permission:  enable ? 1 : 0,
                }, { headers: { 'X-CSRF-TOKEN': CSRF } })
                .then(() => { input.checked = enable; syncColor(input); })
                .catch(() => {})
                .finally(() => {
                    label.classList.remove('saving');
                    input.disabled = false;
                    resolve();
                });
            });
        }

        updateModuleBadge(roleId, modName);
        updateRoleStrip(roleId);
        showToast((enable ? 'Granted' : 'Revoked') + ' all in ' + modName, true);
    });
});

/* ── Grant / Revoke All (whole role) ────────────────────────────── */
async function grantRevokeAll(enable) {
    const panel = document.getElementById('panel-' + activeRoleId);
    if (!panel) return;

    const targets = [...panel.querySelectorAll('.perm-toggle')]
        .filter(i => !i.disabled && (enable ? !i.checked : i.checked));

    if (targets.length === 0) { showToast('Nothing to change', true); return; }

    for (const input of targets) {
        await new Promise(resolve => {
            const label = input.closest('.ts');
            label.classList.add('saving');
            input.disabled = true;

            axios.post(UPDATE, {
                role_id:         input.dataset.role,
                permission_name: input.dataset.permission,
                has_permission:  enable ? 1 : 0,
            }, { headers: { 'X-CSRF-TOKEN': CSRF } })
            .then(() => { input.checked = enable; syncColor(input); })
            .catch(() => {})
            .finally(() => {
                label.classList.remove('saving');
                input.disabled = false;
                resolve();
            });
        });
    }

    panel.querySelectorAll('.mod-card[data-mod]').forEach(card => {
        updateModuleBadge(activeRoleId, card.dataset.mod);
    });
    updateRoleStrip(activeRoleId);
    showToast((enable ? 'Granted' : 'Revoked') + ' all permissions for this role', true);
}

document.getElementById('btnGrantAll').addEventListener('click',  () => grantRevokeAll(true));
document.getElementById('btnRevokeAll').addEventListener('click', () => grantRevokeAll(false));

/* ── Tab switching ──────────────────────────────────────────────── */
document.querySelectorAll('.rtab').forEach(tab => {
    tab.addEventListener('click', function () {
        document.querySelectorAll('.rtab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.role-panel').forEach(p => p.classList.remove('active'));
        this.classList.add('active');
        activeRoleId = this.dataset.roleTab;
        document.getElementById('panel-' + activeRoleId)?.classList.add('active');
        applySearch(document.getElementById('permSearch').value.trim().toLowerCase());
    });
});

/* ── Search ─────────────────────────────────────────────────────── */
function applySearch(q) {
    const panel = document.getElementById('panel-' + activeRoleId);
    if (!panel) return;
    let anyVisible = false;

    panel.querySelectorAll('.mod-card').forEach(card => {
        let cardHas = false;
        card.querySelectorAll('.perm-item').forEach(item => {
            const match = !q || item.dataset.perm.includes(q) || item.dataset.label.includes(q);
            item.classList.toggle('hidden-perm', !match);
            if (match) cardHas = true;
        });
        card.classList.toggle('hidden-card', !cardHas);
        if (cardHas) anyVisible = true;
    });

    const noRes = document.getElementById('noResults-' + activeRoleId);
    if (noRes) noRes.style.display = anyVisible || !q ? 'none' : 'block';
}

document.getElementById('permSearch').addEventListener('input', function () {
    applySearch(this.value.trim().toLowerCase());
});

})();
</script>
@endpush
