@extends('layouts.app')

@section('title')
    Treatment Sessions Summary
@endsection

@push('css')
    <style>
        /* Table styling */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            text-align: center;
            padding: 8px;
            border: 1px solid #ddd;
        }
        thead {
            background-color: #f8f9fa;
        }
    </style>
@endpush

@section('content')
    <x-page-title title="Treatment Sessions" subtitle="Summary" />

    <div class="row">
        <div class="col-xl-12 mx-auto">
            <div class="card">
                <div class="card-body">

                    <h5 class="mb-3">Treatment Sessions Summary</h5>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Session ID</th>
                                    <th>Patient Name</th>
                                    <th>Doctor Name</th>
                                    <th>Total Sessions</th>
                                    <th>Remaining Sessions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sessions as $session)
                                    <tr>
                                        <td>{{ $session['session_id'] }}</td>
                                        <td>{{ $session['patient_name'] }}</td>
                                        <td>{{ $session['doctor_name'] }}</td>
                                        <td>{{ $session['total_sessions'] }}</td>
                                        <td>{{ $session['remaining_sessions'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <!-- End table -->

                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    {{-- Core Plugins --}}
    <script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/input-tags/js/tagsinput.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/main.js') }}"></script>
@endpush
