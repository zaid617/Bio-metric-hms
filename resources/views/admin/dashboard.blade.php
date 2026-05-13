@extends('layouts.app')


<!-- Font Awesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<!-- External Dashboard CSS -->
<link href="{{ asset('css/dashboard.css') }}" rel="stylesheet">
@section('title')
    Clinic Dashboard
@endsection

@section('content')

    @php
        $hideFinancialCards = auth()->user()?->hasRole('super-admin') ?? false;
    @endphp

    <!-- Page Heading -->
    <div class="dashboard-title">
        <p class="text-muted">Welcome to the Clinic Dashboard</p>
    </div>

    <!-- All Branches -->
    <div class="row">
    @foreach($branchStats as $branch)
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card shadow border-0 h-100">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-white ">{{ $branch['branch_name'] }}</h5>
                    <input type="date"
                        class="branch-date-picker form-control form-control-sm w-auto text-white bg-primary border-0"
                        data-branch-id="{{ $branch['branch_id'] }}"
                        value="{{ now()->toDateString() }}">
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Doctors -->
                        <div class="col-6 mb-2">
                            <a href="{{ route('doctors.index', ['branch_id' => $branch['branch_id'] ?? null]) }}" class="text-decoration-none text-reset d-block">
                            <div class="d-flex align-items-center p-3 stat-card-custom border border-primary">
                                <i class="fas fa-user-md fa-2x text-primary me-3"></i>
                                <div>
                                    <h6 class="mb-0">{{ $branch['totalDoctors'] ?? 0 }}</h6>
                                    <small>Doctors</small>
                                </div>
                            </div>
                            </a>
                        </div>
                        <!-- Patients -->
                        <div class="col-6 mb-2">
                            <a href="{{ route('patients.index', ['branch_id' => $branch['branch_id'] ?? null]) }}" class="text-decoration-none text-reset d-block">
                            <div class="d-flex align-items-center p-3 stat-card-custom border border-success">
                                <i class="fas fa-users fa-2x text-success me-3"></i>
                                <div>
                                    <h6 class="mb-0">{{ $branch['totalPatients'] ?? 0 }}</h6>
                                    <small>Patients</small>
                                </div>
                            </div>
                            </a>
                        </div>
                        <!-- Employees -->
                        <div class="col-6 mb-2">
                            <div class="d-flex align-items-center p-3 stat-card-custom border border-secondary">
                                <i class="fas fa-id-badge fa-2x text-secondary me-3"></i>
                                <div>
                                    <h6 class="mb-0">{{ $branch['totalEmployees'] ?? 0 }}</h6>
                                    <small>Employees</small>
                                </div>
                            </div>
                        </div>
                        <!-- Appointments -->
                        <div class="col-6 mb-2">
                            <a href="{{ route('consultations.index', ['branch_id' => $branch['branch_id'] ?? null]) }}" class="text-decoration-none text-reset d-block">
                            <div class="d-flex align-items-center p-3 stat-card-custom border border-info">
                                <i class="fas fa-stethoscope fa-2x text-info me-3"></i>
                                <div>
                                    <h6 class="mb-0">{{ $branch['totalAppointments'] ?? 0 }}</h6>
                                    <small>Appointments</small>
                                </div>
                            </div>
                            </a>
                        </div>
                        <!-- Today Consultations -->
                        <div class="col-6 mb-2" id="consultations-{{ $branch['branch_id'] }}">
                            <a id="consultations-link-{{ $branch['branch_id'] }}"
                               data-branch-id="{{ $branch['branch_id'] }}"
                               href="{{ route('consultations.index', ['branch_id' => $branch['branch_id'] ?? null, 'date_from' => now()->toDateString(), 'date_to' => now()->toDateString()]) }}"
                               class="text-decoration-none text-reset d-block">
                            <div class="d-flex align-items-center p-3 stat-card-custom border border-info">
                                <i class="fas fa-handshake fa-2x text-info me-3"></i>
                                <div>
                                    <h6 class="mb-0">{{ $branch['totalConsultationsToday'] ?? 0 }}</h6>
                                    <small>Consultations</small>
                                </div>
                            </div>
                            </a>
                        </div>
                        <!-- Sessions -->
                        <div class="col-6 mb-2" id="sessions-{{ $branch['branch_id'] }}">
                            <a id="sessions-link-{{ $branch['branch_id'] }}"
                               data-branch-id="{{ $branch['branch_id'] }}"
                               href="{{ route('sessions.index', ['branch_id' => $branch['branch_id'] ?? null, 'date_from' => now()->toDateString(), 'date_to' => now()->toDateString()]) }}"
                               class="text-decoration-none text-reset d-block">
                            <div class="d-flex align-items-center p-3 stat-card-custom border border-warning">
                                <i class="fas fa-calendar-check fa-2x text-warning me-3"></i>
                                <div>
                                    <h6 class="mb-0">{{ $branch['totalSessionsToday'] ?? 0 }}</h6>
                                    <small>Sessions</small>
                                </div>
                            </div>
                            </a>
                        </div>
                        <!-- Total Payments -->
                        @if(!$hideFinancialCards)
                        <div class="col-6" id="today-payments-{{ $branch['branch_id'] }}">
                            <a id="today-payments-link-{{ $branch['branch_id'] }}"
                               data-branch-id="{{ $branch['branch_id'] }}"
                               href="{{ route('payments.appointment-invoices', ['branch_id' => $branch['branch_id'] ?? null, 'date_from' => now()->toDateString(), 'date_to' => now()->toDateString()]) }}"
                               class="text-decoration-none text-reset d-block">
                            <div class="d-flex align-items-center p-3 stat-card-custom border border-danger">
                                <i class="fas fa-coins fa-2x text-danger me-3"></i>
                                <div>
                                    <h6 class="mb-0">
                                        {{ number_format($branch['totalPaymentsToday'] ?? 0) }}
                                    </h6>
                                    <small>This Day Payments</small>
                                </div>
                            </div>
                            </a>
                        </div>
                        <!-- Total Payments -->
                        <div class="col-6" id="total-payments-{{ $branch['branch_id'] }}">
                            <a id="month-payments-link-{{ $branch['branch_id'] }}"
                               data-branch-id="{{ $branch['branch_id'] }}"
                               href="{{ route('payments.appointment-invoices', ['branch_id' => $branch['branch_id'] ?? null, 'date_from' => now()->startOfMonth()->toDateString(), 'date_to' => now()->endOfMonth()->toDateString()]) }}"
                               class="text-decoration-none text-reset d-block">
                            <div class="d-flex align-items-center p-3 stat-card-custom border border-danger">
                                <i class="fas fa-coins fa-2x text-danger me-3"></i>
                                <div>
                                    <h6 class="mb-0">
                                        {{ number_format($branch['totalPaymentsAll'] ?? 0, 0) }}
                                    </h6>
                                    <small>Month Payments</small>
                                </div>
                            </div>
                            </a>
                        </div>
                        <div class="col-6" id="consultation-pending-{{ $branch['branch_id'] }}">
                            <a href="{{ route('payments.appointment-invoices', ['branch_id' => $branch['branch_id'] ?? null, 'status' => 'outstanding']) }}" class="text-decoration-none text-reset d-block">
                            <div class="d-flex align-items-center p-3 stat-card-custom border border-warning">
                                <i class="fas fa-file-invoice-dollar fa-2x text-warning me-3"></i>
                                <div>
                                    <h6 class="mb-0">
                                        {{ number_format($branch['consultationPendingTotal'] ?? 0, 0) }}
                                    </h6>
                                    <small>Consultation Pending</small>
                                </div>
                            </div>
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach
    </div>




    <!-- Overall Stats Card -->
    @php
        $overallDoctors = collect($branchStats)->sum('totalDoctors');
        $overallPatients = collect($branchStats)->sum('totalPatients');
        $overallEmployees = collect($branchStats)->sum('totalEmployees');
        $overallAppointments = collect($branchStats)->sum('totalAppointments');
        $overallSessions = collect($branchStats)->sum('totalSessionsToday');
        $overallPayments = collect($branchStats)->sum(fn($b) => ($b['checkupPaymentsToday'] ?? 0) + ($b['sessionPaymentsToday'] ?? 0));
        $overallConsultationPending = collect($branchStats)->sum('consultationPendingTotal');
    @endphp

    <!-- Overall Heading -->

    <div class="card shadow border-0 mb-4">
    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-white">{{ $overallTitle ?? 'Overall Branches' }}</h5>
        <small>{{ \Carbon\Carbon::now()->format('d M Y') }}</small>
    </div>
    <div class="card-body">
        <div class="row text-center">
            <!-- Doctors -->
            <div class="col-4 col-md-2 mb-3">
                <div class="d-flex align-items-center p-3 bg-light rounded shadow-sm">
                    <i class="fas fa-user-md fa-2x text-primary me-3"></i>
                    <div>
                        <h6 class="mb-0">{{ $overallDoctors }}</h6>
                        <small>Doctors</small>
                    </div>
                </div>
            </div>
            <!-- Patients -->
            <div class="col-4 col-md-2 mb-3">
                <div class="d-flex align-items-center p-3 bg-light rounded shadow-sm">
                    <i class="fas fa-users fa-2x text-success me-3"></i>
                    <div>
                        <h6 class="mb-0">{{ $overallPatients }}</h6>
                        <small>Patients</small>
                    </div>
                </div>
            </div>
            <!-- Checkups -->
            <div class="col-4 col-md-2 mb-3">
                <div class="d-flex align-items-center p-3 bg-light rounded shadow-sm">
                    <i class="fas fa-id-badge fa-2x text-secondary me-3"></i>
                    <div>
                        <h6 class="mb-0">{{ $overallEmployees }}</h6>
                        <small>Employees</small>
                    </div>
                </div>
            </div>
            <!-- Sessions -->
            <div class="col-4 col-md-2 mb-3">
                <div class="d-flex align-items-center p-3 bg-light rounded shadow-sm">
                    <i class="fas fa-stethoscope fa-2x text-info me-3"></i>
                    <div>
                        <h6 class="mb-0">{{ $overallAppointments }}</h6>
                        <small>Appointments</small>
                    </div>
                </div>
            </div>
            <!-- Total Payments -->
            @if(!$hideFinancialCards)
            <div class="col-4 col-md-2 mb-3">
                <div class="d-flex align-items-center p-3 bg-light rounded shadow-sm">
                    <i class="fas fa-coins fa-2x text-dark me-3"></i>
                    <div>
                        <h6 class="mb-0">{{ number_format($overallPayments, 0) }}</h6>
                        <small>Total</small>
                    </div>
                </div>
            </div>
            <div class="col-4 col-md-2 mb-3">
                <div class="d-flex align-items-center p-3 bg-light rounded shadow-sm">
                    <i class="fas fa-file-invoice-dollar fa-2x text-warning me-3"></i>
                    <div>
                        <h6 class="mb-0">{{ number_format($overallConsultationPending, 0) }}</h6>
                        <small>Pending</small>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@endsection

@push('script')
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/main.js') }}"></script>

    <script>
        $(document).ready(function() {
            const consultationsBaseUrl = @json(route('consultations.index'));
            const sessionsBaseUrl = @json(route('sessions.index'));
            const appointmentInvoicesBaseUrl = @json(route('payments.appointment-invoices'));

            const buildUrl = (baseUrl, params) => {
                const url = new URL(baseUrl, window.location.origin);
                Object.entries(params).forEach(([key, value]) => {
                    if (value !== null && value !== undefined && value !== '') {
                        url.searchParams.set(key, value);
                    }
                });
                return url.toString();
            };

            const toNumber = (value) => {
                const num = Number(value);
                return Number.isFinite(num) ? num : 0;
            };

            const formatInt = (value) => {
                return toNumber(value).toLocaleString(undefined, { maximumFractionDigits: 0 });
            };

            // Handle date change for each branch
            $('.branch-date-picker').on('change', function() {
                const branchId = $(this).data('branch-id');
                const selectedDate = $(this).val();

                // Make AJAX request to fetch stats for the selected date
                $.ajax({
                    url: '{{ route("admin.branch.stats") }}',
                    method: 'GET',
                    data: {
                        branch_id: branchId,
                        date: selectedDate
                    },
                    success: function(response) {
                        if (typeof response === 'string') {
                            try {
                                response = JSON.parse(response);
                            } catch (e) {
                                response = {};
                            }
                        }

                        const totalConsultationsToday = (response.totalConsultationsToday !== undefined && response.totalConsultationsToday !== null)
                            ? response.totalConsultationsToday
                            : (response.consultations && response.consultations.count);

                        const totalSessionsToday = (response.totalSessionsToday !== undefined && response.totalSessionsToday !== null)
                            ? response.totalSessionsToday
                            : (response.sessions && response.sessions.count);
                        const totalPaymentsToday = response.totalPaymentsToday;
                        const totalPaymentsAll = response.totalPaymentsAll;
                        const consultationPendingTotal = response.consultationPendingTotal;

                        // Update the stats cards with the new data
                        $(`#consultations-${branchId} h6`).text(formatInt(totalConsultationsToday));
                        $(`#sessions-${branchId} h6`).text(formatInt(totalSessionsToday));
                        $(`#today-payments-${branchId} h6`).text(formatInt(totalPaymentsToday));
                        $(`#total-payments-${branchId} h6`).text(formatInt(totalPaymentsAll));
                        $(`#consultation-pending-${branchId} h6`).text(formatInt(consultationPendingTotal));

                        const monthStart = selectedDate.slice(0, 8) + '01';
                        const selectedDateObj = new Date(selectedDate + 'T00:00:00');
                        const monthEndObj = new Date(selectedDateObj.getFullYear(), selectedDateObj.getMonth() + 1, 0);
                        const monthEnd = `${monthEndObj.getFullYear()}-${String(monthEndObj.getMonth() + 1).padStart(2, '0')}-${String(monthEndObj.getDate()).padStart(2, '0')}`;

                        $(`#consultations-link-${branchId}`).attr('href', buildUrl(consultationsBaseUrl, {
                            branch_id: branchId,
                            date_from: selectedDate,
                            date_to: selectedDate,
                        }));

                        $(`#sessions-link-${branchId}`).attr('href', buildUrl(sessionsBaseUrl, {
                            branch_id: branchId,
                            date_from: selectedDate,
                            date_to: selectedDate,
                        }));

                        $(`#today-payments-link-${branchId}`).attr('href', buildUrl(appointmentInvoicesBaseUrl, {
                            branch_id: branchId,
                            date_from: selectedDate,
                            date_to: selectedDate,
                        }));

                        $(`#month-payments-link-${branchId}`).attr('href', buildUrl(appointmentInvoicesBaseUrl, {
                            branch_id: branchId,
                            date_from: monthStart,
                            date_to: monthEnd,
                        }));
                    },
                    error: function() {
                        alert('Failed to fetch data. Please try again.');
                    }
                });
            });
        });
    </script>
@endpush
