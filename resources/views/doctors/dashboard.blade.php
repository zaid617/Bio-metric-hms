@extends('layouts.app')

@section('title', 'Doctor Dashboard')

@push('css')
    {{-- Font Awesome CSS --}}
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    {{-- Optional page-specific CSS --}}
    <style>
        .card-body .col-md-3 {
            transition: transform 0.2s ease;
        }
        .card-body .col-md-3:hover {
            transform: scale(1.03);
        }
    </style>
@endpush

@section('content')
<div class="container mt-3">

    <!-- Parent Card -->
    <div class="card bg-white shadow rounded">
        <div class="card-header bg-light text-dark d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Welcome, Dr. {{ Auth::user()->name }}</h5>
            <small>{{ \Carbon\Carbon::now()->format('d M Y') }}</small>
        </div>
        <div class="card-body">
            <div class="row g-4 text-center">

                <!-- Appointments Pending -->
                <div class="col-6 col-md-3">
                    <div class="bg-light rounded shadow-sm p-3 d-flex align-items-center justify-content-center">
                        <i class="fas fa-calendar-alt fa-2x text-primary me-2"></i>
                        <div>
                            <h5 class="mb-0">{{ $appointmentsPending }}</h5>
                            <small class="text-muted">Appointments Pending</small>
                        </div>
                    </div>
                </div>

                <!-- Appointments Completed -->
                <div class="col-6 col-md-3">
                    <div class="bg-light rounded shadow-sm p-3 d-flex align-items-center justify-content-center">
                        <i class="fas fa-check-circle fa-2x text-success me-2"></i>
                        <div>
                            <h5 class="mb-0">{{ $appointmentsCompleted }}</h5>
                            <small class="text-muted">Appointments Completed</small>
                        </div>
                    </div>
                </div>

                <!-- Sessions Pending -->
                <div class="col-6 col-md-3">
                    <div class="bg-light rounded shadow-sm p-3 d-flex align-items-center justify-content-center">
                        <i class="fas fa-clock fa-2x text-warning me-2"></i>
                        <div>
                            <h5 class="mb-0">{{ $satisfactorySessionsPending }}</h5>
                            <small class="text-muted">Sessions Pending</small>
                        </div>
                    </div>
                </div>

                <!-- Sessions Completed -->
                <div class="col-6 col-md-3">
                    <div class="bg-light rounded shadow-sm p-3 d-flex align-items-center justify-content-center">
                        <i class="fas fa-check fa-2x text-info me-2"></i>
                        <div>
                            <h5 class="mb-0">{{ $satisfactorySessionsCompleted }}</h5>
                            <small class="text-muted">Sessions Completed</small>
                        </div>
                    </div>
                </div>

                <!-- Today's Sessions Pending -->
                <div class="col-6 col-md-3">
                    <div class="bg-light rounded shadow-sm p-3 d-flex align-items-center justify-content-center">
                        <i class="fas fa-clock fa-2x text-secondary me-2"></i>
                        <div>
                            <h5 class="mb-0">{{ $sessionsTodayPending }}</h5>
                            <small class="text-muted">Today's Sessions Pending</small>
                        </div>
                    </div>
                </div>

                <!-- Today's Sessions Completed -->
                <div class="col-6 col-md-3">
                    <div class="bg-light rounded shadow-sm p-3 d-flex align-items-center justify-content-center">
                        <i class="fas fa-check-circle fa-2x text-success me-2"></i>
                        <div>
                            <h5 class="mb-0">{{ $sessionsTodayCompleted }}</h5>
                            <small class="text-muted">Today's Sessions Completed</small>
                        </div>
                    </div>
                </div>

                <!-- Today's Patients -->
                <div class="col-6 col-md-3">
                    <div class="bg-light rounded shadow-sm p-3 d-flex align-items-center justify-content-center">
                        <i class="fas fa-users fa-2x text-danger me-2"></i>
                        <div>
                            <h5 class="mb-0">{{ $patientsTodayCount }}</h5>
                            <small class="text-muted">Today's Patients</small>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
    {{-- jQuery --}}
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    {{-- Core UI Plugins --}}
    <script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/input-tags/js/tagsinput.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/main.js') }}"></script>

    {{-- Font Awesome JS --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>

    {{-- Page specific animations or interactivity --}}
    <script>
        $(function() {
            console.log('Doctor Dashboard ready ✅');
        });
    </script>
@endpush
