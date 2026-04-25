<!--start sidebar-->
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
        <!--navigation-->
        <ul class="metismenu" id="sidenav">


 <!--navigation-->

        @php
            $sidebarUser = auth()->user();
            $isViewOnlyAdmin = $sidebarUser?->hasRole('view-only-admin') ?? false;
            $dashboardRoute = $isViewOnlyAdmin ? route('view-only-admin.dashboard') : route('admin.dashboard');
            $appointmentsIndexRoute = $isViewOnlyAdmin ? route('view-only-admin.appointments.index') : route('admin.appointments.index');
            $appointmentsCreateRoute = $isViewOnlyAdmin ? route('view-only-admin.appointments.create') : route('admin.appointments.create');
        @endphp

         @hasanyrole('admin|view-only-admin')
          @can('view_dashboard')
          <li>
            <a href="{{ $dashboardRoute }}">
              <div class="parent-icon"><i class="material-icons-outlined">home</i>
              </div>
              <div class="menu-title">Dashboard</div>
            </a>
          </li>
          @endcan

          @canany(['view patients', 'create patients', 'edit patients', 'delete patients'])
          <li>
            <a class="has-arrow" href="javascript:;">
              <div class="parent-icon"><i class="material-icons-outlined">person</i>
              </div>
              <div class="menu-title">Patients</div>
            </a>
            <ul>
              @can('view patients')
              <li><a href="{{ url('/patients') }}"><i class="material-icons-outlined">list</i>All Patients</a>
              </li>
              @endcan
              @can('create patients')
              <li><a href="{{ url('/patients/create') }}"><i class="material-icons-outlined">add</i>Add New Patient</a>
              </li>
              @endcan

            </ul>
          </li>
          @endcanany

          @can('manage_appointments')
          <li>
            <a class="has-arrow" href="javascript:;">
                <div class="parent-icon">
                <i class="material-icons-outlined">medical_services</i>
                </div>
                <div class="menu-title">Doctors</div>
            </a>
            <ul>
                <li>
                    <a href="{{ url('/doctors') }}">
                        <i class="material-icons-outlined">list</i> All Doctors
                    </a>
                </li>
                <li>
                    <a href="{{ url('/doctors/create') }}">
                        <i class="material-icons-outlined">person_add</i> Add New Doctor
                    </a>
                </li>
            </ul>
            </li>
            @endcan

             <!-- Checkups Menu -->
             @canany(['view appointments', 'create appointments'])
             <li>
                <a class="has-arrow" href="javascript:;">
                    <div class="parent-icon">
                    <i class="material-icons-outlined">assignment</i>
                    </div>
                    <div class="menu-title">Appointments</div>
                </a>
                <ul>
                    @can('view appointments')
                    <li>
                    <a href="{{ $appointmentsIndexRoute }}">
                        <i class="material-icons-outlined">fact_check</i> All Appointments
                    </a>
                    </li>
                    @endcan
                    @can('create appointments')
                    <li>
                    <a href="{{ $appointmentsCreateRoute }}">
                        <i class="material-icons-outlined">add_circle</i>  Book Appointment
                    </a>
                    </li>
                    @endcan
                </ul>
            </li>
            @endcanany

            <!-- Doctor Consultation Checkups -->
            @can('view consultation')
            <li>
                <a class="has-arrow" href="javascript:;">
                    <div class="parent-icon">
                    <i class="material-icons-outlined">local_hospital</i>
                    </div>
                    <div class="menu-title">Dr Consultations</div>
                </a>
                <ul>
                    <li>
                    <a href="{{ url('doctor-consultations/0') }}">
                        <i class="material-icons-outlined">medical_information</i> Dr Checkup
                    </a>
                    </li>
                    <li>
                    <a href="{{ url('/doctor-consultations/1') }}">
                        <i class="material-icons-outlined">history</i> Completed Consultations
                    </a>
                    </li>
                </ul>
            </li>
            @endcan

             <!-- Enrollments -->
            @can('view enrollment')
            <li>
                <a class="has-arrow" href="javascript:;">
                    <div class="parent-icon">
                    <i class="material-icons-outlined">school</i>
                    </div>
                    <div class="menu-title">Enrollments</div>
                </a>
                <ul>
                     <li>
                    <a href="{{ url('/enrollments/0') }}">
                        <i class="material-icons-outlined">fact_check</i> Pending Enrollments
                    </a>
                    </li>
                    <li>
                    <a href="{{ url('/enrollments/1') }}">
                        <i class="material-icons-outlined">fact_check</i> Completed Enrollments
                    </a>
                    </li>
                    <li>
                </ul>
            </li>
            @endcan

            <!--  Sessions -->
            @can('manage_sessions')
            <li>
                <a class="has-arrow" href="javascript:;">
                    <div class="parent-icon">
                    <i class="material-icons-outlined">event</i>
                    </div>
                    <div class="menu-title">Sessions</div>
                </a>
                <ul>
                    <li>
                    <a href="{{ url('/ongoing-sessions/1') }}">
                        <i class="material-icons-outlined">fact_check</i> Ongoing Sessions
                    </a>
                    </li>
                    <li>
                    <a href="{{ url('/ongoing-sessions/2') }}">
                        <i class="material-icons-outlined">history</i> Completed Sessions
                    </a>
                    </li>
                </ul>
            </li>
            @endcan

            {{-- Feedback --}}
            @can('view feedback')
            <li class="{{ request()->is('feedback*') ? 'mm-active' : '' }}">
                <a class="has-arrow" href="javascript:;">
                    <div class="parent-icon"><i class="material-icons-outlined">feedback</i></div>
                    <div class="menu-title">Feedback</div>
                </a>
                <ul class="{{ request()->is('feedback*') ? 'mm-show' : '' }}">
                    <li class="{{ request()->is('feedback/doctor-list') ? 'mm-active' : '' }}">
                        <a href="{{ url('/feedback/doctor-list') }}">
                            <i class="material-icons-outlined">fact_check</i>Doctor Feedback
                        </a>
                    </li>
                    <li class="{{ request()->is('feedback/patient-list') ? 'mm-active' : '' }}">
                        <a href="{{ url('/feedback/patient-list') }}">
                            <i class="material-icons-outlined">history</i>Patient Feedback
                        </a>
                    </li>
                </ul>
            </li>
            @endcan

            <!-- Payments Menu -->
            @canany(['view payments', 'view returns'])
            <li>
                <a class="has-arrow" href="javascript:;">
                    <div class="parent-icon">
                        <i class="material-icons-outlined">account_balance_wallet</i>
                    </div>
                    <div class="menu-title">Payments</div>
                </a>
                <ul>
                    @can('view payments')
                    <li>
                        <a href="{{ url('/payments/outstanding-invoices') }}">
                            <i class="material-icons-outlined">receipt_long</i> Outstanding Invoices
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('/payments/completed-invoices') }}">
                            <i class="material-icons-outlined">task_alt</i> Completed Invoices
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('/payments/appointment-invoices') }}">
                            <i class="material-icons-outlined">description</i> Appointment Invoices
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('/payments/receivable') }}">
                            <i class="material-icons-outlined">payments</i> Payment Receivable
                        </a>
                    </li>
                    @endcan
                    @can('view returns')
                    <li>
                        <a href="{{ url('/payments/return-payments') }}">
                            <i class="material-icons-outlined">undo</i> Payment Returns
                        </a>
                    </li>
                    @endcan
                </ul>
            </li>
            @endcanany

            <!-- Accounts Menu -->
            @can('view_reports')
            <li>
                <a class="has-arrow" href="javascript:;">
                    <div class="parent-icon">
                    <i class="material-icons-outlined">badge</i>
                    </div>
                    <div class="menu-title">Employees</div>
                </a>
                <ul>
                    <li>
                    <a href="{{ url('/employees') }}">
                        <i class="material-icons-outlined">group</i> All Employees
                    </a>
                    </li>
                    <li>
                    <a href="{{ url('/employees/create') }}">
                        <i class="material-icons-outlined">person_add</i> Add New Employee
                    </a>
                    </li>
                    <li>
                    <a href="{{ url('/employees/salaries') }}">
                        <i class="material-icons-outlined">attach_money</i> Salaries
                    </a>
                    </li>
                </ul>
            </li>
            @endcan

            <!-- Attendance Management -->
            @canany(['view attendance devices', 'view attendance records', 'view payroll'])
            <li>
                <a class="has-arrow" href="javascript:;">
                    <div class="parent-icon">
                    <i class="material-icons-outlined">fingerprint</i>
                    </div>
                    <div class="menu-title">Attendance</div>
                </a>
                <ul>
                    @can('view attendance devices')
                    <li>
                    <a href="{{ route('attendance.devices.index') }}">
                        <i class="material-icons-outlined">devices</i> Devices
                    </a>
                    </li>
                    @endcan
                    @can('view attendance records')
                    <li>
                    <a href="{{ route('attendance.records.index') }}">
                        <i class="material-icons-outlined">fact_check</i> Attendance Records
                    </a>
                    </li>
                    @endcan
                    @can('view payroll')
                    <li>
                    <a href="{{ route('attendance.payroll.index') }}">
                        <i class="material-icons-outlined">payments</i> Payroll
                    </a>
                    </li>
                    @endcan
                </ul>
            </li>
            @endcanany

            <!-- Expenses Management -->
            @canany(['manage_payments', 'view_reports'])
            <li>
                <a class="has-arrow" href="javascript:;">
                    <div class="parent-icon">
                    <i class="material-icons-outlined">money_off</i>
                    </div>
                    <div class="menu-title">Expenses</div>
                </a>
                <ul>
                    @can('manage_payments')
                    <li>
                    <a href="{{ url('/expense-types') }}">
                        <i class="material-icons-outlined">category</i> Expense Types
                    </a>
                    </li>
                    <li>
                    <a href="{{ url('/expenses/create') }}">
                        <i class="material-icons-outlined">add_circle</i> Create Expense
                    </a>
                    </li>
                    @endcan
                    @can('view_reports')
                    <li>
                    <a href="{{ url('/expenses') }}">
                        <i class="material-icons-outlined">visibility</i> View Expenses
                    </a>
                    </li>
                    @endcan
                </ul>
            </li>
            @endcanany

             <!-- General Settings Menu -->
             @canany(['manage_appointments', 'manage_payments'])
             <li>
                <a class="has-arrow" href="javascript:;">
                    <div class="parent-icon">
                    <i class="material-icons-outlined">settings</i>
                    </div>
                    <div class="menu-title">General Settings</div>
                </a>
                <ul>
                    @can('manage_appointments')
                    <li>
                    <a href="{{ url('/branches') }}">
                        <i class="material-icons-outlined">store</i> Branches
                    </a>
                    </li>
                    @endcan
                    @can('manage_payments')
                    <li>
                    <a href="{{ url('/banks') }}">
                        <i class="material-icons-outlined">account_balance_wallet</i> Banks
                    </a>
                    </li>
                    @endcan
                    @can('manage_appointments')
                    <li>
                    <a href="{{ url('/settings/general') }}">
                        <i class="material-icons-outlined">tune</i> Branch Fee Settings
                    </a>
                    </li>
                    <li>
                    <a href="{{ route('payroll.settings.index') }}">
                        <i class="material-icons-outlined">payments</i> Payroll Settings
                    </a>
                    </li>
                    @endcan
                </ul>
            </li>
            @endcanany


   <!-- Users Menu -->
@if($sidebarUser && !$isViewOnlyAdmin && ($sidebarUser->hasRole('admin') || $sidebarUser->can('manage_appointments')))
<li>
    <a class="has-arrow" href="javascript:;" aria-expanded="false">
        <div class="parent-icon">
            <i class="material-icons-outlined">supervised_user_circle</i>
        </div>
        <div class="menu-title">Users</div>
    </a>
    <ul class="mm-collapse">

        @role('admin')
        <li>
            <a href="{{ url('roles-permissions') }}">
                <i class="material-icons-outlined">account_balance_wallet</i> Roles Permissions
            </a>
        </li>
        @endrole
        @can('manage_appointments')
        <li>
            <a href="{{ url('/users') }}">
                <i class="material-icons-outlined">group</i> Add Users
            </a>
        </li>
        @endcan
    </ul>
</li>
@endif

               {{--Reporting--}}
@can('view_reports')
<li>
    <a class="has-arrow" href="javascript:;">
        <div class="parent-icon">
            <i class="material-icons-outlined">bar_chart</i>
        </div>
        <div class="menu-title">Reporting</div>
    </a>
    <ul>
        <li>
            <a href="{{ url('/ledger') }}">
                <i class="material-icons-outlined">store</i> Branch Ledger
            </a>
        </li>
        <li>
            <a href="{{ url('/bank-ledger') }}">
                <i class="material-icons-outlined">account_balance</i> Bank Ledger
            </a>
        </li>
        <li>
            <a href="{{ url('/income-report') }}">
                <i class="material-icons-outlined">list_alt</i> All Transaction
            </a>
        </li>
    </ul>
</li>
@endcan



            {{--session Table--}}
            @can('view payments')
            <li>
                <a href="{{ url('/payments/outstandings') }}">
                <div class="parent-icon"><i class="material-icons-outlined">widgets</i></div>
                <div class="menu-title">Payments Outstandings</div>
                </a>
            </li>
            @endcan

            {{--Salary Records--}}
            @can('view_reports')
            <li>
                <a href="{{ url('/salaries') }}">
                <div class="parent-icon"><i class="material-icons-outlined">widgets</i></div>
                <div class="menu-title">Salary Records</div>
                </a>
            </li>
            @endcan

             {{--Payments Transactions--}}
            @can('manage_payments')
            <li>
                <a href="{{ url('/transfer') }}">
                <div class="parent-icon"><i class="material-icons-outlined">widgets</i></div>
                <div class="menu-title">Payments Transactions</div>
                </a>
            </li>
            @endcan

    {{-- Doctor Availability Menu --}}
@can('manage_appointments')
<li class="menu-label">Doctor Availability</li>

@php
    if($sidebarUser && $sidebarUser->hasAnyRole(['admin', 'view-only-admin'])){
        $doctors = \App\Models\Doctor::all();
    } else {
        $doctors = $sidebarUser && $sidebarUser->doctor ? collect([$sidebarUser->doctor]) : collect();
    }

    $activeDoctor = request()->route('doctor') ?? null;
@endphp

<li class="has-sub {{ request()->is('doctors/*/availability*') ? 'active' : '' }}">
    <a href="javascript:void(0);" class="parent-link">
        <div class="parent-icon">
            <i class="material-icons-outlined">calendar_today</i>
        </div>
        <div class="menu-title">Doctor Availability</div>
    </a>
    <ul class="sub-menu">
        @foreach($doctors as $doctor)
        <li class="{{ $activeDoctor == $doctor->id ? 'active' : '' }}">
            <a href="{{ route('doctors.availability.index', ['doctor' => $doctor->id, 'showForm' => 1]) }}">
                {{ $doctor->name }}
            </a>
        </li>
        @endforeach
    </ul>
</li>
@endcan



          @endhasanyrole
{{-- ==================Docter Menu==================== --}}
          @auth('doctor')
          <li>
            <a href="{{ url('doctor/dashboard') }}">
              <div class="parent-icon"><i class="material-icons-outlined">home</i>
              </div>
              <div class="menu-title">Doctor Dashboard</div>
            </a>
          </li>
          @endauth

         </ul>
        <!--end navigation-->
    </div>
  </aside>
<!--end sidebar-->
