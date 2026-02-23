@extends('layouts.app')

@section('title')
    Treatment Sessions
@endsection

@push('css')
    {{-- DataTables CSS --}}
    {{-- DataTables CSS --}}
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet"> {{-- 👈 Buttons CSS --}}

    <style>
        /* Allow dropdowns to overflow */
        .table-responsive {
            overflow: visible;
        }
    </style>
@endpush

@section('content')
    <x-page-title title="Treatment Sessions" subtitle="List of all treatment sessions" />

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #f8f9fa;">
                    <h5 class="mb-0 text-dark">Consultations List</h5>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table id="sessions-table" class="table table-bordered table-hover dataTable no-footer">
                            <thead class="table-dark">
                                <tr>
                                    <th>Sr No</th>
                                    <th>Invoice</th>
                                    <th>Date</th>
                                    <th>MR-Patient</th>
                                    <th>Patient</th>
                                    <th>Doctor</th>
                                    <th>Diagnosis</th>
                                    <th>Note</th>
                                    <th>Sanction Doctor</th>
                                    <th>Enrollment</th>
                                    <th style="width:220px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $count = 0;
                                @endphp
                                @foreach ($enrollments as $session)
                                    @php
                                        $count++;
                                        $total = $session->sessionTimes->count();
                                        $completed = $session->sessionTimes->where('is_completed', true)->count();
                                        $remaining = $total - $completed;
                                    @endphp
                                    <tr>
                                        <td>{{ $count }}</td>
                                        <td>{{ $session->checkup_id }}</td>
                                        <td>{{ date('d-m-Y', strtotime($session->created_at)) ?? 'N/A' }}</td>
                                        <td>{{ $session->patient?->mr ?? 'N/A' }}</td>
                                        <td>{{ $session->patient?->name ?? 'N/A' }}</td>
                                        <td>{{ $session->checkup?->doctor ? $session->checkup->doctor->first_name . ' ' . $session->checkup->doctor->last_name : 'N/A' }}
                                        </td>
                                        <td>{{ $session->diagnosis ?? '-' }}</td>
                                        <td>{{ $session->note ?? '-' }}</td>
                                        <td>{{ doctor_get_name($session->ss_dr_id) }}</td>

                                        {{-- Sessions Info --}}
                                        <td>
                                            @if ($session->enrollment_status == 0)
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            @elseif ($session->enrollment_status == 1)
                                                <span class="badge bg-success">Completed</span>
                                            @elseif ($session->enrollment_status == 2)
                                                <span class="badge bg-success">Completed</span>
                                            @elseif ($session->enrollment_status == 3)
                                                <span class="badge bg-danger">Cancelled</span>
                                            @else
                                                <span class="badge bg-secondary">Unknown</span>
                                            @endif
                                        </td>

                                        {{-- Actions Dropdown --}}
                                        <td class="text-center">
                                            @if($session->enrollment_status == 0)
                                              <a href="{{ route('treatment-sessions.sessions', $session->id) }}" class="btn btn-sm btn-info mb-1 w-100">Sessions</a>
                                            @else
                                              <a href="{{ route('session-details', $session->id) }}" class="btn btn-sm btn-info mb-1 w-100">View Sessions</a>
                                             @endif
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
   {{-- jQuery --}}
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    {{-- DataTables JS --}}
    {{-- DataTables JS --}}
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    {{-- Buttons Extension JS --}}
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

     {{-- Core Plugins --}}
    <script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/input-tags/js/tagsinput.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/main.js') }}"></script>




    <script>
        $(document).ready(function() {
            var table = $('#sessions-table').DataTable({
                responsive: false, // ❌ disable responsive (so columns na hide ho)
                scrollX: true, // ✅ horizontal scroll enable
                ordering: true,
                searching: true,
                pageLength: 5,
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "All"]
                ],
                // Remove fixed widths and let columns auto adjust
                autoWidth: false,
                scrollX: true,
                columnDefs: [
                    {
                        targets: [8, 9],
                        orderable: false,
                        searchable: false
                    }
                ],
                drawCallback: function() {
                    $('#sessions-table').css('width', '100%');
                },
                createdRow: function(row, data, dataIndex) {
                    $(row).find('td').css('vertical-align', 'middle');
                },
                dom: "<'row mb-3'<'col-md-4'l><'col-md-4 text-end'B><'col-md-4'f>>" +
                    "<'row'<'col-sm-12'tr>>" +
                    "<'row mt-2'<'col-md-5'i><'col-md-7'p>>",
                buttons: [
                    { extend: 'copy', text: 'Copy', className: 'btn btn-sm btn-light' },
                    { extend: 'csv', text: 'CSV', className: 'btn btn-sm btn-light' },
                    { extend: 'excel', text: 'Excel', className: 'btn btn-sm btn-light' },
                    { extend: 'pdf', text: 'PDF', className: 'btn btn-sm btn-light' },
                    { extend: 'print', text: 'Print', className: 'btn btn-sm btn-light' }
                ]
            });

            // Styling fix for search + length dropdown
            $('.dataTables_filter input').addClass('form-control form-control-sm');
            $('.dataTables_length select').addClass('form-select form-select-sm');
        });
    </script>
@endpush
