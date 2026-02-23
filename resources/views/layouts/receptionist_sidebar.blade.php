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

            {{-- Dashboard --}}
            @can('view_dashboard')
            <li class="{{ request()->is('receptionist-dashboard') ? 'mm-active' : '' }}">
                <a href="{{ url('/receptionist/dashboard') }}">
                    <div class="parent-icon"><i class="material-icons-outlined">home</i></div>
                    <div class="menu-title">Dashboard</div>
                </a>
            </li>
            @endcan

            {{-- Patients --}}
            @can('view patients')
            <li class="{{ request()->is('patients*') ? 'mm-active' : '' }}">
                <a class="has-arrow" href="javascript:;">
                    <div class="parent-icon"><i class="material-icons-outlined">person</i></div>
                    <div class="menu-title">Patients</div>
                </a>
                <ul class="{{ request()->is('patients*') ? 'mm-show' : '' }}">
                    @can('view patients')
                    <li class="{{ request()->is('patients') ? 'mm-active' : '' }}">
                        <a href="{{ url('/patients') }}"><i class="material-icons-outlined">list</i>All Patients</a>
                    </li>
                    @endcan
                    @can('create patients')
                    <li class="{{ request()->is('patients/create') ? 'mm-active' : '' }}">
                        <a href="{{ url('/patients/create') }}"><i class="material-icons-outlined">add</i>Add New Patient</a>
                    </li>
                    @endcan
                </ul>
            </li>
            @endcan

            {{-- Appointments --}}
            @can('view appointments')
            <li class="{{ request()->is('checkups*') ? 'mm-active' : '' }}">
                <a class="has-arrow" href="javascript:;">
                    <div class="parent-icon"><i class="material-icons-outlined">assignment</i></div>
                    <div class="menu-title">Appointments</div>
                </a>
                <ul class="{{ request()->is('checkups*') ? 'mm-show' : '' }}">
                    @can('view appointments')
                    <li class="{{ request()->is('checkups') ? 'mm-active' : '' }}">
                        <a href="{{ url('/receptionist/appointments') }}"><i class="material-icons-outlined">fact_check</i>All Appointments</a>
                    </li>
                    @endcan
                    @can('create appointments')
                    <li class="{{ request()->is('checkups/create') ? 'mm-active' : '' }}">
                        <a href="{{ url('/receptionist/appointments/create') }}"><i class="material-icons-outlined">add_circle</i>Book Appointment</a>
                    </li>
                    @endcan
                </ul>
            </li>
            @endcan

            {{-- Doctor Consultations --}}
            @can('view consultation')
            <!-- Doctor Consultation Checkups -->
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

            {{-- Enrollments --}}
            @can('view enrollment')
            <li class="{{ request()->is('enrollments*') ? 'mm-active' : '' }}">
                <a class="has-arrow" href="javascript:;">
                    <div class="parent-icon"><i class="material-icons-outlined">school</i></div>
                    <div class="menu-title">Enrollments</div>
                </a>
                <ul class="{{ request()->is('enrollments*') ? 'mm-show' : '' }}">
                    <li class="{{ request()->is('enrollments/0') ? 'mm-active' : '' }}">
                        <a href="{{ url('/enrollments/0') }}"><i class="material-icons-outlined">fact_check</i>Pending Enrollments</a>
                    </li>
                    <li class="{{ request()->is('enrollments/1') ? 'mm-active' : '' }}">
                        <a href="{{ url('/enrollments/1') }}"><i class="material-icons-outlined">fact_check</i>Completed Enrollments</a>
                    </li>
                </ul>
            </li>
            @endcan

            {{-- Sessions --}}
            @can('manage_sessions')
            <li class="{{ request()->is('ongoing-sessions*') ? 'mm-active' : '' }}">
                <a href="{{ url('/ongoing-sessions/1') }}">
                    <div class="parent-icon"><i class="material-icons-outlined">event</i></div>
                    <div class="menu-title">Sessions</div>
                </a>
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

 <!-- Expenses Management -->
            <li>
                <a class="has-arrow" href="javascript:;">
                    <div class="parent-icon">
                    <i class="material-icons-outlined">money_off</i>
                    </div>
                    <div class="menu-title">Expenses</div>
                </a>
                <ul>
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
                    <li>
                    <a href="{{ url('/expenses') }}">
                        <i class="material-icons-outlined">visibility</i> View Expenses
                    </a>
                    </li>
                </ul>
            </li>



 <!-- Payments Menu -->
            <li>
                <a class="has-arrow" href="javascript:;">
                    <div class="parent-icon">
                        <i class="material-icons-outlined">account_balance_wallet</i>
                    </div>
                    <div class="menu-title">Payments</div>
                </a>
                <ul>
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
                        <a href="{{ url('/payments/receivable') }}">
                            <i class="material-icons-outlined">payments</i> Payment Receivable
                        </a>
                    </li>
                    <li>


            {{-- Payments Returns --}}
            @can('view returns')
            <li class="{{ request()->is('payments/return-payments*') ? 'mm-active' : '' }}">
                <a href="{{ url('/payments/return-payments') }}">
                    <div class="parent-icon"><i class="material-icons-outlined">account_balance_wallet</i></div>
                    <div class="menu-title">Payments Returns</div>
                </a>
            </li>

            
            @endcan

        </ul>
    </div>
</aside>
