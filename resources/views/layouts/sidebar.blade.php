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

         @role('admin','web')
          <li>
            <a href="{{ url('admin/dashboard') }}">
              <div class="parent-icon"><i class="material-icons-outlined">home</i>
              </div>
              <div class="menu-title">Dashboard</div>
            </a>
          </li>

          <li>
            <a class="has-arrow" href="javascript:;">
              <div class="parent-icon"><i class="material-icons-outlined">person</i>
              </div>
              <div class="menu-title">Patients</div>
            </a>
            <ul>
              <li><a href="{{ url('/patients') }}"><i class="material-icons-outlined">list</i>All Patients</a>
              </li>
              <li><a href="{{ url('/patients/create') }}"><i class="material-icons-outlined">add</i>Add New Patient</a>
              </li>

            </ul>
          </li>

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

             <!-- Checkups Menu -->
             <li>
                <a class="has-arrow" href="javascript:;">
                    <div class="parent-icon">
                    <i class="material-icons-outlined">assignment</i>
                    </div>
                    <div class="menu-title">Appointments</div>
                </a>
                <ul>
                    <li>
                    <a href="{{ url('admin/appointments') }}">
                        <i class="material-icons-outlined">fact_check</i> All Appointments
                    </a>
                    </li>
                    <li>
                    <a href="{{ url('admin/appointments/create') }}">
                        <i class="material-icons-outlined">add_circle</i>  Book Appointment
                    </a>
                    </li>
                </ul>
            </li>

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

             <!-- Enrollments -->
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

            <!--  Sessions -->

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

            {{-- Feedback --}}
          
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
                        <a href="{{ url('/payments/return-payments') }}">
                            <i class="material-icons-outlined">undo</i> Payment Returns
                        </a>
                    </li>
                </ul>
            </li>




            <!-- Accounts Menu -->
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

             <!-- General Settings Menu -->
             <li>
                <a class="has-arrow" href="javascript:;">
                    <div class="parent-icon">
                    <i class="material-icons-outlined">settings</i>
                    </div>
                    <div class="menu-title">General Settings</div>
                </a>
                <ul>
                    <li>
                    <a href="{{ url('/branches') }}">
                        <i class="material-icons-outlined">store</i> Branches
                    </a>
                    </li>
                    <li>
                    <a href="{{ url('/banks') }}">
                        <i class="material-icons-outlined">account_balance_wallet</i> Banks
                    </a>
                    </li>
                    <li>
                   
                    </li>
                </ul>
            </li>


   <!-- Users Menu -->
<li>
    <a class="has-arrow" href="javascript:;" aria-expanded="false">
        <div class="parent-icon">
            <i class="material-icons-outlined">supervised_user_circle</i>
        </div>
        <div class="menu-title">Users</div>
    </a>
    <ul class="mm-collapse">
      
        <li>
            <a href="{{ url('roles-permissions') }}">
                <i class="material-icons-outlined">account_balance_wallet</i> Roles Permissions
            </a>
        </li>
        <li>
            <a href="{{ url('/users') }}">
                <i class="material-icons-outlined">group</i> Add Users
            </a>
        </li>
    </ul>
</li>
               {{--Reporting--}}

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

            

            {{--session Table--}}
            <li>
                <a href="{{ url('/payments/outstandings') }}">
                <div class="parent-icon"><i class="material-icons-outlined">widgets</i></div>
                <div class="menu-title">Payments Outstandings</div>
                </a>
            </li>

            {{--Salary Records--}}
            <li>
                <a href="{{ url('/salaries') }}">
                <div class="parent-icon"><i class="material-icons-outlined">widgets</i></div>
                <div class="menu-title">Salary Records</div>
                </a>
            </li>

             {{--Payments Transactions--}}
            <li>
                <a href="{{ url('/transfer') }}">
                <div class="parent-icon"><i class="material-icons-outlined">widgets</i></div>
                <div class="menu-title">Payments Transactions</div>
                </a>
            </li>

    {{-- Doctor Availability Menu --}}
<li class="menu-label">Doctor Availability</li>

@php
    // Admin → saare doctors
    if(auth()->user()->hasRole('admin')){
        $doctors = \App\Models\Doctor::all();
    } else {
        // Doctor login → sirf apna record
        $doctors = auth()->user()->doctor ? collect([auth()->user()->doctor]) : collect();
    }

    // Check if current route is doctor availability
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

          
           
          @endrole
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
