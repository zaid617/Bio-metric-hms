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
    <div class="dashboard-title mb-4">
        <p class="text-muted">Welcome to the Clinic Dashboard</p>
    </div>

    <!-- Single Branch Stats Card -->
    <div class="row g-4">
        <div class="col-12">
            <div class="card shadow border-0 h-100">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-white">{{ $branchStats['branch_name'] }}</h5>
                    <small>{{ now()->format('d M Y') }}</small>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Doctors -->
                        <div class="col-6 col-md-3">
                            <div class="d-flex align-items-center p-3 stat-card-custom border border-primary rounded">
                                <i class="fas fa-user-md fa-2x text-primary me-3"></i>
                                <div>
                                    <h6 class="mb-0">{{ $branchStats['totalDoctors'] ?? 0 }}</h6>
                                    <small>Doctors</small>
                                </div>
                            </div>
                        </div>
                        <!-- Patients -->
                        <div class="col-6 col-md-3">
                            <div class="d-flex align-items-center p-3 stat-card-custom border border-success rounded">
                                <i class="fas fa-users fa-2x text-success me-3"></i>
                                <div>
                                    <h6 class="mb-0">{{ $branchStats['totalPatients'] ?? 0 }}</h6>
                                    <small>Patients</small>
                                </div>
                            </div>
                        </div>
                        <!-- Consultations Today -->
                        <div class="col-6 col-md-3">
                            <div class="d-flex align-items-center p-3 stat-card-custom border border-info rounded">
                                <i class="fas fa-handshake fa-2x text-info me-3"></i>
                                <div>
                                    <h6 class="mb-0">{{ $branchStats['totalConsultationsToday'] ?? 0 }}</h6>
                                    <small>Consultations Today</small>
                                </div>
                            </div>
                        </div>
                        <!-- Sessions Today -->
                        <div class="col-6 col-md-3">
                            <div class="d-flex align-items-center p-3 stat-card-custom border border-warning rounded">
                                <i class="fas fa-calendar-check fa-2x text-warning me-3"></i>
                                <div>
                                    <h6 class="mb-0">{{ $branchStats['totalSessionsToday'] ?? 0 }}</h6>
                                    <small>Sessions Today</small>
                                </div>
                            </div>
                        </div>
                        <!-- Total Payments Today -->
                        <div class="col-6 col-md-3">
                            <div class="d-flex align-items-center p-3 stat-card-custom border border-danger rounded">
                                <i class="fas fa-coins fa-2x text-danger me-3"></i>
                                <div>
                                    <h6 class="mb-0">{{ number_format($branchStats['totalPaymentsToday'] ?? 0) }}</h6>
                                    <small>Total Payments Today</small>
                                </div>
                            </div>
                        </div>
                        <!-- Total Payments In Hand (Cash Only) -->
                        <div class="col-6 col-md-3">
                            <div class="d-flex align-items-center p-3 stat-card-custom border border-danger rounded">
                                <i class="fas fa-coins fa-2x text-danger me-3"></i>
                                <div>
                                    <h6 class="mb-0">{{ number_format($branchStats['totalPaymentsAll'] ?? 0) }}</h6>
                                    <small>Total Payments In Hand</small>
                                </div>
                            </div>
                        </div>
                    </div> <!-- row -->
                </div> <!-- card-body -->
            </div> <!-- card -->
        </div> <!-- col-12 -->
    </div> <!-- row -->

    <!-- Overall Stats Card -->
    @php
        $overallDoctors = $branchStats['totalDoctors'] ?? 0;
        $overallPatients = $branchStats['totalPatients'] ?? 0;
        $overallCheckups = $branchStats['totalConsultationsToday'] ?? 0;
        $overallSessions = $branchStats['totalSessionsToday'] ?? 0;
        $overallPayments = ($branchStats['checkupPaymentsToday'] ?? 0) + ($branchStats['sessionPaymentsToday'] ?? 0);
    @endphp

    <div class="row g-4 mt-3">
        <div class="col-12">
            <div class="card shadow border-0">
                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-white">Overall Branch Stats</h5>
                    <small>{{ now()->format('d M Y') }}</small>
                </div>
                <div class="card-body">
                    <div class="row g-3 text-center">
                        <div class="col-6 col-md-2">
                            <div class="p-3 bg-light rounded shadow-sm">
                                <i class="fas fa-user-md fa-2x text-primary mb-2"></i>
                                <h6 class="mb-0">{{ $overallDoctors }}</h6>
                                <small>Doctors</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="p-3 bg-light rounded shadow-sm">
                                <i class="fas fa-users fa-2x text-success mb-2"></i>
                                <h6 class="mb-0">{{ $overallPatients }}</h6>
                                <small>Patients</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="p-3 bg-light rounded shadow-sm">
                                <i class="fas fa-stethoscope fa-2x text-info mb-2"></i>
                                <h6 class="mb-0">{{ $overallCheckups }}</h6>
                                <small>Checkups</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="p-3 bg-light rounded shadow-sm">
                                <i class="fas fa-calendar-check fa-2x text-warning mb-2"></i>
                                <h6 class="mb-0">{{ $overallSessions }}</h6>
                                <small>Sessions</small>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="p-3 bg-light rounded shadow-sm">
                                <i class="fas fa-coins fa-2x text-dark mb-2"></i>
                                <h6 class="mb-0">{{ number_format($overallPayments, 0) }}</h6>
                                <small>Total Payments</small>
                            </div>
                        </div>
                    </div> <!-- row -->
                </div> <!-- card-body -->
            </div> <!-- card -->
        </div> <!-- col-12 -->
    </div> <!-- row -->

@endsection

@push('script')
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/main.js') }}"></script>
@endpush
