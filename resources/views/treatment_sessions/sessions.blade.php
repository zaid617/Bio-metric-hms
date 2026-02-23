@extends('layouts.app')

@section('title', 'Ongoing Sessions')

@push('css')
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">

<style>
    /* Fix for DataTables header alignment */
    .dataTables_wrapper .dataTables_scroll {
        overflow: auto;
    }
    .dataTables_scrollBody {
        min-height: 200px;
    }
    .dropdown-menu {
        z-index: 9999 !important;
    }
    table.dataTable thead th {
        position: relative !important;
    }
    .dataTables_wrapper {
        position: relative;
    }
    .btn-group {
        position: static !important;
    }
    .dropdown-menu .btn {
        border: none !important;
        text-align: left;
        padding: 8px 12px;
        font-size: 14px;
    }
    .dropdown-menu .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .dropdown-divider {
        margin: 5px 0;
    }
</style>
@endpush

@section('content')
<x-page-title title="Ongoing Sessions" subtitle="List of all Ongoing Sessions" />

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center bg-primary">
                <h5 class="mb-0 text-white">Ongoing Sessions</h5>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="sessions-table" class="table table-bordered table-hover dataTable no-footer align-middle" style="width:100%">
                        <thead class="table-dark">
                            <tr>
                                <th>Sr No</th>
                                <th>Invoice</th>
                                <th>Date</th>
                                <th>MR</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Sanction Doctor</th>
                                <th>Diagnosis</th>
                                <th>Note</th>
                                <th>Total Sessions</th>
                                <th>Remaining Sessions</th>
                                <th>Completed Sessions</th>
                                <th>Status</th>
                                <th style="width:150px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $count = 0; @endphp
                            @foreach ($sessions as $session)
                                @php $count++; @endphp
                                <tr>
                                    <td>{{ $count }}</td>
                                    <td>{{ $session->id }}</td>
                                    <td>{{ date('d-m-Y', strtotime($session->created_at)) ?? 'N/A' }}</td>
                                    <td>{{ $session->patient?->mr ?? 'N/A' }}</td>
                                    <td>{{ $session->patient?->name ?? 'N/A' }}</td>
                                    <td>{{ $session->checkup?->doctor ? $session->checkup->doctor->first_name . ' ' . $session->checkup->doctor->last_name : 'N/A' }}</td>
                                    <td>{{ doctor_get_name($session->ss_dr_id) }}</td>
                                    <td>{{ $session->diagnosis ?? '-' }}</td>
                                    <td>{{ $session->note ?? '-' }}</td>
                                    <td>{{ $session->session_count }}</td>
                                    <td>{{ $session->pending_count }}</td>
                                    <td>{{ $session->completed_count }}</td>
                                    <td>
                                        @if ($session->status === 0)
                                            <span class="badge bg-warning text-dark">Not Enroll</span>
                                        @elseif ($session->status === 1)
                                            <span class="badge bg-primary">Ongoing</span>
                                        @elseif ($session->status === 2)
                                            <span class="badge bg-success">Completed</span>
                                        @elseif ($session->status === 3)
                                            <span class="badge bg-danger">Cancelled</span>
                                        @else
                                            <span class="badge bg-secondary">Unknown</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-outline-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-cog me-1"></i>Actions
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end p-2" style="min-width: 200px;">
                                                <a href="{{ route('session-details', $session->id) }}" class="dropdown-item d-flex align-items-center text-primary mb-2">
                                                    <i class="fas fa-eye me-2"></i>Details
                                                </a>
                                                <a href="{{ route('treatment-sessions.edit', $session->id) }}" class="dropdown-item d-flex align-items-center text-warning mb-2">
                                                    <i class="fas fa-edit me-2"></i>Edit
                                                </a>
                                                @if ($session->status === 2)
                                                    <div class="dropdown-divider my-2"></div>
                                                    <a href="{{ url('/feedback/doctor/' . $session->id) }}" class="dropdown-item d-flex align-items-center text-info mb-2">
                                                        <i class="fas fa-stethoscope me-2"></i>Doctor Feedback
                                                    </a>
                                                    <a href="{{ url('/feedback/patient/' . $session->id) }}" class="dropdown-item d-flex align-items-center text-success">
                                                        <i class="fas fa-user me-2"></i>Patient Feedback
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

{{-- DataTables Buttons --}}
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

{{-- DataTables Responsive --}}
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

{{-- Font Awesome --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>

{{-- Optional: Core Plugins --}}
<script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
<script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
<script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
<script src="{{ URL::asset('build/js/main.js') }}"></script>

<script>
$(document).ready(function() {
    var table = $('#sessions-table').DataTable({
        scrollX: true,
        responsive: true,
        ordering: true,
        searching: true,
        pageLength: 5,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        autoWidth: false,
        dom: "<'row mb-3'<'col-md-4'l><'col-md-4 text-center'B><'col-md-4'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row mt-2'<'col-md-5'i><'col-md-7'p>>",
        buttons: [
            { extend: 'copy', text: 'Copy', className: 'btn btn-sm btn-light' },
            { extend: 'csv', text: 'CSV', className: 'btn btn-sm btn-light' },
            { extend: 'excel', text: 'Excel', className: 'btn btn-sm btn-light' },
            { extend: 'pdf', text: 'PDF', className: 'btn btn-sm btn-light' },
            { extend: 'print', text: 'Print', className: 'btn btn-sm btn-light' }
        ],
        initComplete: function() {
            this.api().columns.adjust();
        },
        drawCallback: function() {
            $('.dropdown-toggle').dropdown();
        }
    });

    // Fix dropdown positioning
    $('#sessions-table').on('show.bs.dropdown', function(e) {
        var dropdown = $(e.target).closest('.btn-group').find('.dropdown-menu');
        var tableContainer = $(this).closest('.dataTables_scrollBody');
        if (tableContainer.length) {
            dropdown.appendTo('body').addClass('dataTables-dropdown');
        }
    });

    $('#sessions-table').on('hide.bs.dropdown', function(e) {
        var dropdown = $(e.target).closest('.btn-group').find('.dropdown-menu');
        var btnGroup = $(e.target).closest('.btn-group');
        if (dropdown.hasClass('dataTables-dropdown')) {
            dropdown.appendTo(btnGroup).removeClass('dataTables-dropdown');
        }
    });

    $('.dataTables_filter input').addClass('form-control form-control-sm');
    $('.dataTables_length select').addClass('form-select form-select-sm');
    $(window).on('resize', function() {
        table.columns.adjust();
    });
});
</script>
@endpush
