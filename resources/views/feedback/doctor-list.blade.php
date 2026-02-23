@extends('layouts.app')

@section('title', 'Doctor Feedback List')

@push('css')
    {{-- DataTables CSS --}}
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">

    <style>
        /* Optional: table dropdown overflow handling */
        .table-responsive {
            overflow: visible;
        }
    </style>
@endpush

@section('content')
<div class="container">

    <h3>Doctor Feedback List</h3>

    @if(session('success'))
        <div style="color:green; margin-bottom: 10px;">
            {{ session('success') }}
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            @if($feedbacks->isEmpty())
                <p>No doctor feedback available.</p>
            @else
                <div class="table-responsive">
                    <table id="doctorFeedbackTable" class="table table-bordered table-striped">
                        <thead class="table-primary">
                            <tr>
                                <th>ID</th>
                                <th>Session ID</th>
                                <th>Doctor Name</th>
                                <th>Patient Name</th>
                                <th>Doctor Remarks</th>
                                <th>Satisfaction (%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($feedbacks as $fb)
                                <tr>
                                    <td>{{ $fb->id }}</td>
                                    <td>{{ $fb->sessionsid }}</td>
                                    <td>{{ $fb->doctor_name ?? '-' }}</td>
                                    <td>{{ $fb->patient_name ?? '-' }}</td>
                                    <td>{{ $fb->doctor_remarks ?? '-' }}</td>
                                    <td>{{ $fb->satisfaction }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
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

    {{-- DataTables Responsive JS --}}
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

    {{-- Optional: Core Plugins if needed --}}
    <script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/main.js') }}"></script>

    {{-- DataTable Initialization --}}
    <script>
        $(document).ready(function () {
            $('#doctorFeedbackTable').DataTable({
                responsive: true,
                autoWidth: false,
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50, 100],
                ordering: true,
            });
        });
    </script>
@endpush
