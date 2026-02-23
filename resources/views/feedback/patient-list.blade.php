@extends('layouts.app')

@section('title', 'Patient Feedback List')

@push('css')
    {{-- DataTables CSS --}}
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <style>
        /* Optional: table dropdown overflow handling (agar zarurat ho) */
        .table-responsive {
            overflow: visible;
        }
    </style>
@endpush

@section('content')
<div class="container">
    <h3>Patient Feedback List</h3>

    @if(session('success'))
        <div style="color:green; margin-bottom: 10px;">
            {{ session('success') }}
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            @if($feedbacks->isNotEmpty())
                <div class="table-responsive">
                    <table id="patientFeedbackTable" class="table table-bordered table-striped">
                        <thead class="table-primary">
                            <tr>
                                <th>ID</th>
                                <th>Session ID</th>
                                <th>Doctor ID</th>
                                <th>Patient ID</th>
                                <th>Patient Name</th>
                                <th>Patient Remarks</th>
                                <th>Satisfaction (%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($feedbacks as $feedback)
                                <tr>
                                    <td>{{ $feedback->id }}</td>
                                    <td>{{ $feedback->sessionsid }}</td>
                                    <td>{{ $feedback->doctorid }}</td>
                                    <td>{{ $feedback->patientid }}</td>
                                    <td>{{ $feedback->patient_name ?? '-' }}</td>
                                    <td>{{ $feedback->patient_remarks ?? '-' }}</td>
                                    <td>{{ $feedback->satisfaction }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p>No patient feedback available.</p>
            @endif
        </div>
    </div>
</div>
@endsection

@push('script')
    {{-- jQuery --}}
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    {{-- DataTables JS --}}
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    {{-- Core Plugins from your Doctors page --}}
    <script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/input-tags/js/tagsinput.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/main.js') }}"></script>

    {{-- DataTable Initialization --}}
    <script>
        $(document).ready(function () {
            $('#patientFeedbackTable').DataTable({
                responsive: true,
                autoWidth: false,
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50, 100],
                ordering: true,
            });
        });
    </script>
@endpush
