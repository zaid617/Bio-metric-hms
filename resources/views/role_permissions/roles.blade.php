@extends('layouts.app')

@section('title', 'Role Permissions')

@push('css')
<style>
/* ══════════════════════════════════════════════════════════════
   Role-tab permission editor
══════════════════════════════════════════════════════════════ */

/* ── Tab bar ─────────────────────────────────────────────────── */
.role-tab-bar {
    display: flex; gap: .4rem; flex-wrap: wrap;
    padding-bottom: .85rem;
    border-bottom: 1px solid rgba(255,255,255,.07);
    margin-bottom: 1.25rem;
}
.rtab {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 8px 16px; border-radius: 8px; cursor: pointer;
    font-size: .76rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
    border: 2px solid transparent; opacity: .45; user-select: none;
    transition: opacity .15s, transform .12s, box-shadow .15s;
}
.rtab:hover { opacity: .75; }
.rtab.active {
    opacity: 1; border-color: rgba(255,255,255,.4);
    transform: translateY(-2px); box-shadow: 0 5px 18px rgba(0,0,0,.4);
}
.rtab-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.rtab-count {
    background: rgba(0,0,0,.3); border-radius: 10px;
    padding: 1px 7px; font-size: .7rem; line-height: 1.5;
}

/* ── Toolbar ─────────────────────────────────────────────────── */
.rp-toolbar { display: flex; align-items: center; gap: .6rem; flex-wrap: wrap; margin-bottom: 1.1rem; }
.rp-search { position: relative; flex: 1; max-width: 300px; }
.rp-search input {
    padding-left: 2.2rem; border-radius: 8px;
    background: rgba(255,255,255,.06); border-color: rgba(255,255,255,.12); color: #fff;
}
.rp-search input::placeholder { color: #666; }
.rp-search input:focus {
    background: rgba(255,255,255,.09); border-color: rgba(255,255,255,.22);
    box-shadow: none; color: #fff;
}
.rp-search .si {
    position: absolute; left: .65rem; top: 50%; transform: translateY(-50%);
    font-size: 1rem; color: #555; pointer-events: none;
}

/* ── Role panel ──────────────────────────────────────────────── */
.role-panel { display: none; }
.role-panel.active { display: block; }

/* ── Role summary strip ──────────────────────────────────────── */
.role-strip {
    display: flex; align-items: center; gap: 1.25rem; flex-wrap: wrap;
    padding: 14px 20px; border-radius: 12px; margin-bottom: 1.25rem;
}
.rs-name { font-size: 1rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
.rs-big { font-size: 2.2rem; font-weight: 900; line-height: 1; }
.rs-sub { font-size: .7rem; opacity: .7; }
.rs-progress-wrap { flex: 1; min-width: 150px; }
.rs-progress-track { height: 7px; border-radius: 4px; background: rgba(0,0,0,.25); overflow: hidden; }
.rs-progress-fill { height: 100%; border-radius: 4px; transition: width .5s ease; }
.sa-bypass-badge {
    display: inline-flex; align-items: center; gap: 5px; font-size: .72rem;
    color: #f59e0b; padding: 4px 12px; border-radius: 20px;
    background: rgba(245,158,11,.1); border: 1px solid rgba(245,158,11,.3);
}
.sa-bypass-badge .material-icons-outlined { font-size: .9rem; }

/* ── Permission card grid ────────────────────────────────────── */
.perm-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(295px, 1fr));
    gap: 1rem;
}

/* ── Module card ─────────────────────────────────────────────── */
.mod-card {
    border-radius: 12px; border: 1px solid rgba(255,255,255,.08);
    background: rgba(255,255,255,.025); overflow: hidden;
}
.mod-card.hidden-card { display: none; }

.mod-card-head {
    display: flex; align-items: center; gap: 9px;
    padding: 11px 14px; border-bottom: 1px solid rgba(255,255,255,.06);
}
.mod-card-icon {
    width: 32px; height: 32px; border-radius: 7px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
}
.mod-card-icon .material-icons-outlined { font-size: 1.05rem; }
.mod-card-name {
    font-weight: 700; font-size: .78rem; letter-spacing: .05em;
    text-transform: uppercase; flex: 1;
}
.mod-card-badge {
    font-size: .64rem; padding: 2px 7px; border-radius: 20px; white-space: nowrap;
}

/* Toggle-all pill */
.tog-all {
    display: inline-flex; align-items: center; gap: 3px; cursor: pointer;
    font-size: .66rem; padding: 2px 8px; border-radius: 20px;
    border: 1px solid rgba(255,255,255,.1); color: #888; white-space: nowrap;
    transition: border-color .15s, color .15s; flex-shrink: 0;
}
.tog-all:hover { border-color: rgba(255,255,255,.28); color: #ccc; }
.tog-all .material-icons-outlined { font-size: .8rem; }

/* ── Permission row ──────────────────────────────────────────── */
.perm-item {
    display: flex; align-items: center; gap: 10px; padding: 9px 14px;
    border-bottom: 1px solid rgba(255,255,255,.035);
    transition: background .1s;
}
.perm-item:last-child { border-bottom: none; }
.perm-item:hover { background: rgba(255,255,255,.04); }
.perm-item.hidden-perm { display: none; }
.pi-info { flex: 1; min-width: 0; }
.pi-name { font-size: .8rem; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pi-slug { font-size: .65rem; color: #5a5a6e; font-family: monospace; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* ── Toggle switch ───────────────────────────────────────────── */
.ts {
    position: relative; display: inline-block;
    width: 40px; height: 22px; flex-shrink: 0; cursor: pointer;
}
.ts input { position: absolute; opacity: 0; width: 0; height: 0; }
.ts-track {
    position: absolute; inset: 0; border-radius: 11px;
    background: rgba(255,255,255,.1); transition: background .2s;
}
.ts-thumb {
    position: absolute; width: 16px; height: 16px; border-radius: 50%;
    top: 3px; left: 3px; background: #888;
    transition: transform .2s, background .2s;
    box-shadow: 0 1px 4px rgba(0,0,0,.5);
}
.ts input:checked ~ .ts-thumb { transform: translateX(18px); background: #fff; }
.ts.saving .ts-track { background: rgba(255,255,255,.07) !important; }
.ts.saving .ts-thumb { background: #444; animation: pulse .8s ease infinite; }

/* Lock badge (super-admin) */
.sa-lk {
    display: inline-flex; align-items: center; gap: 3px; font-size: .64rem;
    color: #f59e0b; padding: 2px 8px; border-radius: 20px; flex-shrink: 0;
    background: rgba(245,158,11,.1); border: 1px solid rgba(245,158,11,.22); white-space: nowrap;
}
.sa-lk .material-icons-outlined { font-size: .75rem; }

/* ── Toast ───────────────────────────────────────────────────── */
.rp-toast {
    position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 9999;
    min-width: 220px; border-radius: 10px; font-size: .84rem;
    box-shadow: 0 8px 24px rgba(0,0,0,.4); pointer-events: none;
}

/* ── No results ──────────────────────────────────────────────── */
.no-results-box { text-align: center; padding: 3rem 1rem; color: #666; display: none; }

/* ── Responsive ──────────────────────────────────────────────── */
@media (max-width: 640px) {
    .perm-grid { grid-template-columns: 1fr; }
    .rtab { padding: 6px 10px; font-size: .7rem; }
    .role-strip { gap: .75rem; padding: 12px 14px; }
}

@keyframes pulse {
    0%,100% { opacity: 1; }
    50%      { opacity: .4; }
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

{{-- ── Page header ─────────────────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0 fw-bold">Role Permissions</h5>
        <small class="text-muted">{{ $totalPerms }} permissions &middot; {{ $webRoles->count() }} roles</small>
    </div>
    @if($isSuperAdmin)
    <span class="badge" style="background:#f59e0b;color:#000;font-size:.74rem;padding:6px 14px;border-radius:20px;">
        <span class="material-icons-outlined" style="font-size:.9rem;vertical-align:-2px;">shield</span>
        Super Admin
    </span>
    @endif
</div>

{{-- ── Toolbar ──────────────────────────────────────────────────── --}}
<div class="rp-toolbar">
    <div class="rp-search">
        <span class="si material-icons-outlined">search</span>
        <input type="text" id="permSearch" class="form-control form-control-sm" placeholder="Search permissions…">
    </div>
    <button class="btn btn-sm btn-outline-success" id="btnGrantAll" title="Grant all to current role">
        <span class="material-icons-outlined" style="font-size:.9rem;vertical-align:-3px;">done_all</span>
        Grant All
    </button>
    <button class="btn btn-sm btn-outline-danger" id="btnRevokeAll" title="Revoke all from current role">
        <span class="material-icons-outlined" style="font-size:.9rem;vertical-align:-3px;">remove_done</span>
        Revoke All
    </button>
</div>

{{-- ── Role tab bar ────────────────────────────────────────────── --}}
<div class="role-tab-bar" id="roleTabBar">
    @foreach($webRoles as $i => $role)
    @php
        $s   = $roleStyle[$role->name] ?? ['bg'=>'#555','text'=>'#fff'];
        $cnt = $roleCounts[$role->id];
        $tabTextColor = $s['text'] === '#000' ? $s['bg'] : '#fff';
    @endphp
    <div class="rtab {{ $i === 0 ? 'active' : '' }}"
         data-role-tab="{{ $role->id }}"
         style="background:{{ $s['bg'] }}1f; color:{{ $tabTextColor }}; border-color:{{ $s['bg'] }}55;">
        <span class="rtab-dot" style="background:{{ $s['bg'] }};"></span>
        {{ role_display_name($role->name) }}
        <span class="rtab-count" id="rtab-count-{{ $role->id }}">
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
    $isLocked = $isLocked = ($role->name === 'admin');
    $pct  = ($totalPerms > 0 && !$isLocked) ? round($cnt / $totalPerms * 100) : 0;
@endphp

<div class="role-panel {{ $i === 0 ? 'active' : '' }}" id="panel-{{ $role->id }}" data-role="{{ $role->id }}">

    {{-- Role summary strip --}}
    <div class="role-strip" style="background:{{ $s['bg'] }}14; border:1px solid {{ $s['bg'] }}30;">
        <div>
            <div class="rs-name" style="color:{{ $s['bg'] }};">
                {{ role_display_name($role->name) }}
            </div>
            <div class="rs-sub" style="color:{{ $s['bg'] }};">role</div>
        </div>
        <div style="text-align:center; min-width:60px;">
            <div class="rs-big" style="color:{{ $s['bg'] }};">{{ $isLocked ? '∞' : $cnt }}</div>
            <div class="rs-sub">{{ $isLocked ? 'all perms' : 'of '.$totalPerms }}</div>
        </div>
        @if(!$isLocked)
        <div class="rs-progress-wrap">
            <div class="d-flex justify-content-between mb-1" style="font-size:.67rem; color:#888;">
                <span>Coverage</span>
                <span id="rs-pct-{{ $role->id }}">{{ $pct }}%</span>
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
                <div class="mod-card-icon" style="background:{{ $s['bg'] }}22;">
                    <span class="material-icons-outlined" style="color:{{ $s['bg'] }};">
                        {{ $modIcon[$modName] ?? 'circle' }}
                    </span>
                </div>
                <span class="mod-card-name">{{ ucfirst(str_replace('-', ' ', $modName)) }}</span>
                <span class="mod-card-badge" id="badge-{{ $role->id }}-{{ $modName }}"
                      style="background:rgba(255,255,255,.06); color:#999;">
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
        <span class="material-icons-outlined" style="font-size:3rem; color:#555;">search_off</span>
        <p class="mt-2">No permissions match your search.</p>
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
    toastEl.style.color      = ok ? '#000'     : '#fff';
    toastEl.classList.add('show');
    toastTimer = setTimeout(() => toastEl.classList.remove('show'), 2600);
}

/* ── Toggle track color ─────────────────────────────────────────── */
function syncColor(input) {
    const track = input.nextElementSibling; // .ts-track
    track.style.background = input.checked ? input.dataset.color : 'rgba(255,255,255,.1)';
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
