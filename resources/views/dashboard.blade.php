@extends('layouts.app')

@section('title')
    Clinic Dashboard
@endsection

@section('content')
    <x-page-title title="Dashboard" subtitle="Clinic Management" />

    <div class="row">
        {{-- Total Doctors --}}
        <div class="col-12 col-lg-4">
            <div class="card radius-10">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div>
                            <h4 class="mb-0">{{ $totalDoctors }}</h4>
                        </div>
                        <div class="ms-auto">
                            <i class="bx bx-group fs-3 text-primary"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mt-3">
                        <p class="mb-0">Total Doctors</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Total Patients --}}
        <div class="col-12 col-lg-4">
            <div class="card radius-10">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div>
                            <h4 class="mb-0">{{ $totalPatients }}</h4>
                        </div>
                        <div class="ms-auto">
                            <i class="bx bx-user-plus fs-3 text-success"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mt-3">
                        <p class="mb-0">Total Patients</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Total Checkups --}}
        <div class="col-12 col-lg-4">
            <div class="card radius-10">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div>
                            <h4 class="mb-0">{{ $totalCheckups }}</h4>
                        </div>
                        <div class="ms-auto">
                            <i class="bx bx-clipboard fs-3 text-info"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mt-3">
                        <p class="mb-0">Total Checkups</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Today's Sessions --}}
        <div class="col-12 col-lg-4">
            <div class="card radius-10 mt-4">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div>
                            <h4 class="mb-0">{{ $totalSessionsToday }}</h4>
                        </div>
                        <div class="ms-auto">
                            <i class="bx bx-calendar-event fs-3 text-warning"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mt-3">
                        <p class="mb-0">Today’s Sessions</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Today's Payment Collection --}}
        <div class="col-12 col-lg-4">
            <div class="card radius-10 mt-4">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div>
                            <h4 class="mb-0">{{ number_format($totalPaymentsToday, 0) }} Rs</h4>
                        </div>
                        <div class="ms-auto">
                            <i class="bx bx-wallet fs-3 text-danger"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mt-3">
                        <p class="mb-0">Today’s Payment Collection</p>
                    </div>
                </div>
            </div>
        </div>
    </div><!--end row-->
@endsection

@push('script')
    <!--plugins-->
    <script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/apexchart/apexcharts.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/peity/jquery.peity.min.js') }}"></script>
    <script>
        $(".data-attributes span").peity("donut")
    </script>
    <script src="{{ URL::asset('build/js/dashboard2.js') }}"></script>
    <script src="{{ URL::asset('build/js/main.js') }}"></script>
@endpush