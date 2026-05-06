@php
    // Resolve the authenticated user from either guard
    $sidebarUser = auth()->check() ? auth()->user() : (auth('doctor')->check() ? auth('doctor')->user() : null);
    $isDoctor    = auth('doctor')->check();

    if ($sidebarUser) {
        // Permission helper — works for both guards transparently
        $can    = fn(string $perm) => $sidebarUser->hasPermissionTo($perm);
        $canAny = fn(array $perms) => collect($perms)->contains(fn($p) => $sidebarUser->hasPermissionTo($p));

        // Role flags
        $isSuperAdmin   = !$isDoctor && $sidebarUser->hasRole('super-admin');
        $isAdmin        = !$isDoctor && ($isSuperAdmin || $sidebarUser->hasRole('admin'));
        $isViewOnly     = !$isDoctor && $sidebarUser->hasRole('view-only-admin');
        $isManager      = !$isDoctor && $sidebarUser->hasRole('manager');
        $isReceptionist = !$isDoctor && $sidebarUser->hasRole('receptionist');

        // Dashboard route
        $dashboardRoute = match (true) {
            $isDoctor    => route('doctor.dashboard'),
            $isViewOnly  => route('view-only-admin.dashboard'),
            $isManager   => route('manager.dashboard'),
            $isReceptionist => route('receptionist.dashboard'),
            default      => route('admin.dashboard'),
        };

        // Appointment index / create routes
        $apptIndex = match (true) {
            $isDoctor       => route('doctor.appointments.index'),
            $isViewOnly     => route('view-only-admin.appointments.index'),
            $isManager      => route('manager.appointments.index'),
            $isReceptionist => route('receptionist.appointments.index'),
            default         => route('admin.appointments.index'),
        };
        $apptCreate = match (true) {
            $isDoctor       => route('doctor.appointments.create'),
            $isViewOnly     => route('view-only-admin.appointments.create'),
            $isManager      => route('manager.appointments.create'),
            $isReceptionist => route('receptionist.appointments.create'),
            default         => route('admin.appointments.create'),
        };
    }
@endphp

<aside class="sidebar-wrapper" data-simplebar="true">
    <div class="sidebar-header">
        <div class="logo-icon">
            <img src="{{ URL::asset('build/images/bodylogo.png') }}" class="logo-img" alt="">
        </div>
        <div class="logo-name flex-grow-1">
            <h5 class="mb-0">Body Experts</h5>
        </div>
        <div class="sidebar-close">
            <span class="material-icons-outlined">close</span>
        </div>
    </div>
    <div class="sidebar-nav">
        <ul class="metismenu" id="sidenav">

@if($sidebarUser)

    {{-- ── DASHBOARD ──────────────────────────────────────────────────── --}}
    @if($can('dashboard.view'))
    <li>
        <a href="{{ $dashboardRoute }}">
            <div class="parent-icon"><i class="material-icons-outlined">home</i></div>
            <div class="menu-title">Dashboard</div>
        </a>
    </li>
    @endif

    {{-- ── PATIENTS ────────────────────────────────────────────────────── --}}
    @if($canAny(['patients.view','patients.create','patients.edit','patients.delete']))
    <li>
        <a class="has-arrow" href="javascript:;">
            <div class="parent-icon"><i class="material-icons-outlined">person</i></div>
            <div class="menu-title">Patients</div>
        </a>
        <ul>
            @if($can('patients.view'))
            <li><a href="{{ url('/patients') }}"><i class="material-icons-outlined">list</i> All Patients</a></li>
            @endif
            @if($can('patients.create'))
            <li><a href="{{ url('/patients/create') }}"><i class="material-icons-outlined">add</i> Add New Patient</a></li>
            @endif
        </ul>
    </li>
    @endif

    {{-- ── DOCTORS ──────────────────────────────────────────────────────── --}}
    @if(!$isDoctor && $canAny(['doctors.view','doctors.create','doctors.edit','doctors.delete']))
    <li>
        <a class="has-arrow" href="javascript:;">
            <div class="parent-icon"><i class="material-icons-outlined">medical_services</i></div>
            <div class="menu-title">Doctors</div>
        </a>
        <ul>
            @if($can('doctors.view'))
            <li><a href="{{ url('/doctors') }}"><i class="material-icons-outlined">list</i> All Doctors</a></li>
            @endif
            @if($can('doctors.create'))
            <li><a href="{{ url('/doctors/create') }}"><i class="material-icons-outlined">person_add</i> Add New Doctor</a></li>
            @endif
        </ul>
    </li>
    @endif

    {{-- ── APPOINTMENTS ─────────────────────────────────────────────────── --}}
    @if($canAny(['appointments.view','appointments.book']))
    <li>
        <a class="has-arrow" href="javascript:;">
            <div class="parent-icon"><i class="material-icons-outlined">assignment</i></div>
            <div class="menu-title">Appointments</div>
        </a>
        <ul>
            @if($can('appointments.view'))
            <li><a href="{{ $apptIndex }}"><i class="material-icons-outlined">fact_check</i> All Appointments</a></li>
            @endif
            @if($can('appointments.book'))
            <li><a href="{{ $apptCreate }}"><i class="material-icons-outlined">add_circle</i> Book Appointment</a></li>
            @endif
        </ul>
    </li>
    @endif

    {{-- ── DR CONSULTATIONS ─────────────────────────────────────────────── --}}
    @if($canAny(['consultations.checkup','consultations.complete']))
    <li>
        <a class="has-arrow" href="javascript:;">
            <div class="parent-icon"><i class="material-icons-outlined">local_hospital</i></div>
            <div class="menu-title">Dr Consultations</div>
        </a>
        <ul>
            @if($can('consultations.checkup'))
            @php $checkupBase = $isDoctor ? url('doctor/consultations') : url('doctor-consultations'); @endphp
            <li><a href="{{ $checkupBase }}/0"><i class="material-icons-outlined">medical_information</i> Dr Checkup</a></li>
            @endif
            @if($can('consultations.complete'))
            <li><a href="{{ $checkupBase }}/1"><i class="material-icons-outlined">history</i> Completed Consultations</a></li>
            @endif
        </ul>
    </li>
    @endif

    {{-- ── ENROLLMENTS ──────────────────────────────────────────────────── --}}
    @if($canAny(['enrollments.pending','enrollments.complete']))
    <li>
        <a class="has-arrow" href="javascript:;">
            <div class="parent-icon"><i class="material-icons-outlined">school</i></div>
            <div class="menu-title">Enrollments</div>
        </a>
        <ul>
            @php $enrollBase = $isDoctor ? url('doctor/enrollments') : url('enrollments'); @endphp
            @if($can('enrollments.pending'))
            <li><a href="{{ $enrollBase }}/0"><i class="material-icons-outlined">pending</i> Pending Enrollments</a></li>
            @endif
            @if($can('enrollments.complete'))
            <li><a href="{{ $enrollBase }}/1"><i class="material-icons-outlined">fact_check</i> Completed Enrollments</a></li>
            @endif
        </ul>
    </li>
    @endif

    {{-- ── SESSIONS ─────────────────────────────────────────────────────── --}}
    @if($canAny(['sessions.ongoing','sessions.completed']))
    <li>
        <a class="has-arrow" href="javascript:;">
            <div class="parent-icon"><i class="material-icons-outlined">event</i></div>
            <div class="menu-title">Sessions</div>
        </a>
        <ul>
            @php $sessBase = $isDoctor ? url('doctor/ongoing-sessions') : url('ongoing-sessions'); @endphp
            @if($can('sessions.ongoing'))
            <li><a href="{{ $sessBase }}/1"><i class="material-icons-outlined">fact_check</i> Ongoing Sessions</a></li>
            @endif
            @if($can('sessions.completed'))
            <li><a href="{{ $sessBase }}/2"><i class="material-icons-outlined">history</i> Completed Sessions</a></li>
            @endif
        </ul>
    </li>
    @endif

    {{-- ── FEEDBACK ─────────────────────────────────────────────────────── --}}
    @if($canAny(['feedback.doctor','feedback.patient']))
    <li>
        <a class="has-arrow" href="javascript:;">
            <div class="parent-icon"><i class="material-icons-outlined">feedback</i></div>
            <div class="menu-title">Feedback</div>
        </a>
        <ul>
            @if($can('feedback.doctor'))
            <li><a href="{{ url('/feedback/doctor-list') }}"><i class="material-icons-outlined">fact_check</i> Doctor Feedback</a></li>
            @endif
            @if($can('feedback.patient'))
            <li><a href="{{ url('/feedback/patient-list') }}"><i class="material-icons-outlined">history</i> Patient Feedback</a></li>
            @endif
        </ul>
    </li>
    @endif

    {{-- ── PAYMENTS ─────────────────────────────────────────────────────── --}}
    @if(!$isDoctor && $canAny(['payments.outstanding-invoices','payments.completed-invoices','payments.appointment-invoices','payments.receivable','payments.return']))
    <li>
        <a class="has-arrow" href="javascript:;">
            <div class="parent-icon"><i class="material-icons-outlined">account_balance_wallet</i></div>
            <div class="menu-title">Payments</div>
        </a>
        <ul>
            @if($can('payments.outstanding-invoices'))
            <li><a href="{{ url('/payments/outstanding-invoices') }}"><i class="material-icons-outlined">receipt_long</i> Outstanding Invoices</a></li>
            @endif
            @if($can('payments.completed-invoices'))
            <li><a href="{{ url('/payments/completed-invoices') }}"><i class="material-icons-outlined">task_alt</i> Completed Invoices</a></li>
            @endif
            @if($can('payments.appointment-invoices'))
            <li><a href="{{ url('/payments/appointment-invoices') }}"><i class="material-icons-outlined">description</i> Appointment Invoices</a></li>
            @endif
            @if($can('payments.receivable'))
            <li><a href="{{ url('/payments/receivable') }}"><i class="material-icons-outlined">payments</i> Payment Receivable</a></li>
            @endif
            @if($can('payments.return'))
            <li><a href="{{ url('/payments/return-payments') }}"><i class="material-icons-outlined">undo</i> Payment Returns</a></li>
            @endif
        </ul>
    </li>
    @endif

    {{-- ── EMPLOYEES ────────────────────────────────────────────────────── --}}
    @if(!$isDoctor && $canAny(['employees.view','employees.create','employees.edit','employees.delete']))
    <li>
        <a class="has-arrow" href="javascript:;">
            <div class="parent-icon"><i class="material-icons-outlined">badge</i></div>
            <div class="menu-title">Employees</div>
        </a>
        <ul>
            @if($can('employees.view'))
            <li><a href="{{ url('/employees') }}"><i class="material-icons-outlined">group</i> All Employees</a></li>
            @endif
            @if($can('employees.create'))
            <li><a href="{{ url('/employees/create') }}"><i class="material-icons-outlined">person_add</i> Add New Employee</a></li>
            @endif
        </ul>
    </li>
    @endif

    {{-- ── ATTENDANCE ───────────────────────────────────────────────────── --}}
    @if(!$isDoctor && $canAny(['attendance.device.create','attendance.records.view','attendance.payroll.view','attendance.payroll.generate','attendance.payroll.adjustments']))
    <li>
        <a class="has-arrow" href="javascript:;">
            <div class="parent-icon"><i class="material-icons-outlined">fingerprint</i></div>
            <div class="menu-title">Attendance</div>
        </a>
        <ul>
            @if($can('attendance.device.create'))
            <li><a href="{{ route('attendance.devices.index') }}"><i class="material-icons-outlined">devices</i> Devices</a></li>
            @endif
            @if($can('attendance.records.view'))
            <li><a href="{{ route('attendance.records.index') }}"><i class="material-icons-outlined">fact_check</i> Attendance Records</a></li>
            @endif
            @if($canAny(['attendance.payroll.view','attendance.payroll.generate']))
            <li><a href="{{ route('attendance.payroll.index') }}"><i class="material-icons-outlined">payments</i> Payroll</a></li>
            @endif
            @if($can('attendance.payroll.adjustments'))
            <li><a href="{{ route('attendance.payroll.adjustments.index') }}"><i class="material-icons-outlined">tune</i> Payroll Adjustments</a></li>
            @endif
        </ul>
    </li>
    @endif

    {{-- ── EXPENSES ─────────────────────────────────────────────────────── --}}
    @if(!$isDoctor && $canAny(['expenses.type.create','expenses.create','expenses.view']))
    <li>
        <a class="has-arrow" href="javascript:;">
            <div class="parent-icon"><i class="material-icons-outlined">money_off</i></div>
            <div class="menu-title">Expenses</div>
        </a>
        <ul>
            @if($can('expenses.type.create'))
            <li><a href="{{ url('/expense-types') }}"><i class="material-icons-outlined">category</i> Expense Types</a></li>
            @endif
            @if($can('expenses.create'))
            <li><a href="{{ url('/expenses/create') }}"><i class="material-icons-outlined">add_circle</i> Create Expense</a></li>
            @endif
            @if($can('expenses.view'))
            <li><a href="{{ url('/expenses') }}"><i class="material-icons-outlined">visibility</i> View Expenses</a></li>
            @endif
        </ul>
    </li>
    @endif

    {{-- ── GENERAL SETTINGS ─────────────────────────────────────────────── --}}
    @if(!$isDoctor && $canAny(['settings.branches.create','settings.branches.edit','settings.bank.create','settings.bank.edit','settings.branch-fee','settings.payroll']))
    <li>
        <a class="has-arrow" href="javascript:;">
            <div class="parent-icon"><i class="material-icons-outlined">settings</i></div>
            <div class="menu-title">General Settings</div>
        </a>
        <ul>
            @if($canAny(['settings.branches.create','settings.branches.edit']))
            <li><a href="{{ url('/branches') }}"><i class="material-icons-outlined">store</i> Branches</a></li>
            @endif
            @if($canAny(['settings.bank.create','settings.bank.edit']))
            <li><a href="{{ url('/banks') }}"><i class="material-icons-outlined">account_balance_wallet</i> Banks</a></li>
            @endif
            @if($can('settings.branch-fee'))
            <li><a href="{{ url('/settings/general') }}"><i class="material-icons-outlined">tune</i> Branch Fee Settings</a></li>
            @endif
            @if($can('settings.payroll'))
            <li><a href="{{ route('payroll.settings.index') }}"><i class="material-icons-outlined">payments</i> Payroll Settings</a></li>
            @endif
        </ul>
    </li>
    @endif

    {{-- ── USERS ────────────────────────────────────────────────────────── --}}
    @php $canManageRoles = !$isDoctor && ($isSuperAdmin || $isAdmin); @endphp
    @if(!$isDoctor && ($canManageRoles || $canAny(['users.view','users.create','users.edit','users.delete'])))
    <li>
        <a class="has-arrow" href="javascript:;">
            <div class="parent-icon"><i class="material-icons-outlined">supervised_user_circle</i></div>
            <div class="menu-title">Users</div>
        </a>
        <ul>
            @if($canManageRoles)
            <li><a href="{{ url('roles-permissions') }}"><i class="material-icons-outlined">admin_panel_settings</i> Role Permissions</a></li>
            @endif
            @if($can('users.view'))
            <li><a href="{{ url('/users') }}"><i class="material-icons-outlined">group</i> All Users</a></li>
            @endif
            @if($can('users.create'))
            <li><a href="{{ url('/users/create') }}"><i class="material-icons-outlined">person_add</i> Add User</a></li>
            @endif
        </ul>
    </li>
    @endif

    {{-- ── REPORTS ──────────────────────────────────────────────────────── --}}
    @if(!$isDoctor && $canAny(['reports.bank-ledgers','reports.branch-ledgers','reports.all-transactions']))
    <li>
        <a class="has-arrow" href="javascript:;">
            <div class="parent-icon"><i class="material-icons-outlined">bar_chart</i></div>
            <div class="menu-title">Reporting</div>
        </a>
        <ul>
            @if($can('reports.branch-ledgers'))
            <li><a href="{{ url('/ledger') }}"><i class="material-icons-outlined">store</i> Branch Ledger</a></li>
            @endif
            @if($can('reports.bank-ledgers'))
            <li><a href="{{ url('/bank-ledger') }}"><i class="material-icons-outlined">account_balance</i> Bank Ledger</a></li>
            @endif
            @if($can('reports.all-transactions'))
            <li><a href="{{ url('/income-report') }}"><i class="material-icons-outlined">list_alt</i> All Transactions</a></li>
            @endif
        </ul>
    </li>
    @endif

    {{-- ── PAYMENT OUTSTANDING ──────────────────────────────────────────── --}}
    @if(!$isDoctor && $can('payment-outstanding.view'))
    <li>
        <a href="{{ url('/payments/outstandings') }}">
            <div class="parent-icon"><i class="material-icons-outlined">widgets</i></div>
            <div class="menu-title">Payments Outstanding</div>
        </a>
    </li>
    @endif

    {{-- ── SALARY RECORDS (quick link) ─────────────────────────────────── --}}
    @if(!$isDoctor && $can('attendance.payroll.view'))
    <li>
        <a href="{{ url('/salaries') }}">
            <div class="parent-icon"><i class="material-icons-outlined">attach_money</i></div>
            <div class="menu-title">Salary Records</div>
        </a>
    </li>
    @endif

    {{-- ── PAYMENTS TRANSACTIONS ────────────────────────────────────────── --}}
    @if(!$isDoctor && $can('payments.receivable'))
    <li>
        <a href="{{ url('/transfer') }}">
            <div class="parent-icon"><i class="material-icons-outlined">swap_horiz</i></div>
            <div class="menu-title">Payments Transactions</div>
        </a>
    </li>
    @endif

    {{-- ── DOCTOR AVAILABILITY (admin / manager) ────────────────────────── --}}
    @if(!$isDoctor && $can('doctors.edit'))
    @php
        $availDoctors = \App\Models\Doctor::all();
        $activeDoctor = request()->route('doctor') ?? null;
    @endphp
    <li class="menu-label">Doctor Availability</li>
    <li class="has-sub {{ request()->is('doctors/*/availability*') ? 'active' : '' }}">
        <a href="javascript:void(0);" class="parent-link has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">calendar_today</i></div>
            <div class="menu-title">Doctor Availability</div>
        </a>
        <ul class="sub-menu">
            @foreach($availDoctors as $doc)
            <li class="{{ $activeDoctor == $doc->id ? 'active' : '' }}">
                <a href="{{ route('doctors.availability.index', ['doctor' => $doc->id, 'showForm' => 1]) }}">
                    {{ $doc->name }}
                </a>
            </li>
            @endforeach
        </ul>
    </li>
    @endif

@else
    <li><p style="color:red; padding:10px;">Please login to access the menu.</p></li>
@endif

        </ul>
    </div>
</aside>
