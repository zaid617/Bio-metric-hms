@extends('layouts.app')


<!-- Font Awesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<!-- External Dashboard CSS -->
<link href="{{ asset('css/dashboard.css') }}" rel="stylesheet">
@section('title')
    Clinic Dashboard
@endsection

@section('content')

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
                    <small>{{ format_date(Now()) }}</small>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Doctors -->
                        <div class="col-6 mb-2">
                            <div class="d-flex align-items-center p-3 stat-card-custom border border-primary">
                                <i class="fas fa-user-md fa-2x text-primary me-3"></i>
                                <div>
                                    <h6 class="mb-0">{{ $branch['totalDoctors'] ?? 0 }}</h6>
                                    <small>Doctors</small>
                                </div>
                            </div>
                        </div>
                        <!-- Patients -->
                        <div class="col-6 mb-2">
                            <div class="d-flex align-items-center p-3 stat-card-custom border border-success">
                                <i class="fas fa-users fa-2x text-success me-3"></i>
                                <div>
                                    <h6 class="mb-0">{{ $branch['totalPatients'] ?? 0 }}</h6>
                                    <small>Patients</small>
                                </div>
                            </div>
                        </div>
                        <!-- Today Consultations -->
                        <div class="col-6 mb-2">
                            <div class="d-flex align-items-center p-3 stat-card-custom border border-info">
                                <i class="fas fa-handshake fa-2x text-info me-3"></i>
                                <div>
                                    <h6 class="mb-0">{{ $branch['totalConsultationsToday'] ?? 0 }}</h6>
                                    <small>Consultations</small>
                                </div>
                            </div>
                        </div>
                        <!-- Sessions -->
                        <div class="col-6 mb-2">
                            <div class="d-flex align-items-center p-3 stat-card-custom border border-warning">
                                <i class="fas fa-calendar-check fa-2x text-warning me-3"></i>
                                <div>
                                    <h6 class="mb-0">{{ $branch['totalSessionsToday'] ?? 0 }}</h6>
                                    <small>Sessions</small>
                                </div>
                            </div>
                        </div>
                        <!-- Total Payments -->
                        <div class="col-6">
                            <div class="d-flex align-items-center p-3 stat-card-custom border border-danger">
                                <i class="fas fa-coins fa-2x text-danger me-3"></i>
                                <div>
                                    <h6 class="mb-0">
                                        {{ number_format($branch['totalPaymentsToday'] ?? 0) }}
                                    </h6>
                                    <small>Total Payments</small>
                                </div>
                            </div>
                        </div>
                        <!-- Total Payments -->
                        <div class="col-6">
                            <div class="d-flex align-items-center p-3 stat-card-custom border border-danger">
                                <i class="fas fa-coins fa-2x text-danger me-3"></i>
                                <div>
                                    <h6 class="mb-0">
                                        {{ number_format($branch['totalPaymentsAll'] ?? 0, 0) }}
                                    </h6>
                                    <small>Total Payments</small>
                                </div>
                            </div>
                        </div>
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
        $overallCheckups = collect($branchStats)->sum('totalCheckups');
        $overallSessions = collect($branchStats)->sum('totalSessionsToday');
        $overallPayments = collect($branchStats)->sum(fn($b) => ($b['checkupPaymentsToday'] ?? 0) + ($b['sessionPaymentsToday'] ?? 0));
    @endphp

    <!-- Overall Heading -->

   <div class="card shadow border-0 mb-4">
    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-white">Overall Branches</h5>
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
                    <i class="fas fa-stethoscope fa-2x text-info me-3"></i>
                    <div>
                        <h6 class="mb-0">{{ $overallCheckups }}</h6>
                        <small>Checkups</small>
                    </div>
                </div>
            </div>
            <!-- Sessions -->
            <div class="col-4 col-md-2 mb-3">
                <div class="d-flex align-items-center p-3 bg-light rounded shadow-sm">
                    <i class="fas fa-calendar-check fa-2x text-warning me-3"></i>
                    <div>
                        <h6 class="mb-0">{{ $overallSessions }}</h6>
                        <small>Sessions</small>
                    </div>
                </div>
            </div>
            <!-- Total Payments -->
            <div class="col-6 col-md-4">
                <div class="d-flex align-items-center p-3 bg-light rounded shadow-sm">
                    <i class="fas fa-coins fa-2x text-dark me-3"></i>
                    <div>
                        <h6 class="mb-0">{{ number_format($overallPayments, 0) }}</h6>
                        <small>Total Payments</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('script')
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/main.js') }}"></script>
@endpush
