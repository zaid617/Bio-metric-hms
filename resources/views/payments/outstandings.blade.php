@extends('layouts.app')
@section('title')
    Payments Outstanding
@endsection

@push('css')
    {{-- DataTables CSS --}}
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <!-- Bootstrap 5 CSS for consistency -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
@endpush

@section('content')
    <x-page-title title="Payments" subtitle="Outstanding Payments" />

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body table-responsive">
                    <table id="outstandingTable" class="table table-striped table-bordered" style="width:100%">
                        <thead class="table-dark">
                            <tr>
                                <th>Sr</th>
                                <th>Date</th>
                                <th>Invoic ID</th>

                                <th>MR</th>
                                <th>Patient Name</th>
                                <th>Dr Name</th>
                                <th>Diagnosis</th>
                                <th>Total Amount</th>
                                <th>Paid</th>
                                <th>Due</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $counter = 1;
                            @endphp
                            @forelse($outstandings as $session)
                                <tr>
                                    <td>{{ $counter++ }}</td>
                                    <td>{{ format_date($session->created_at) }}</td>
                                    <td>{{ $session->id }}</td>
                                    <td>{{ patient_get_mr($session->patient_id) ?? 'N/A' }}</td>
                                    <td>{{ patient_get_name($session->patient_id ) }}</td>
                                    <td>{{ doctor_get_name($session->doctor_id) }}</td>
                                    <td>{{ $session->diagnosis }}</td>
                                    <td>{{ number_format($session->session_fee) }}</td>
                                    <td>{{ number_format($session->paid_amount) }}</td>
                                    <td>{{ number_format($session->dues_amount) }}</td>
                                    <td>
                                        @if ($session->dues_amount > 0)
                                            <span class="badge bg-danger">Outstanding</span>
                                        @else
                                            <span class="badge bg-success">Paid</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('invoice.ledger', $session->id) }}" class="btn btn-sm btn-primary">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center">No outstanding payments found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <!-- Core plugins -->
    <script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <!-- Bootstrap Bundle (Modal, Dropdown fix etc.) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Main JS -->
    <script src="{{ URL::asset('build/js/main.js') }}"></script>

    <script>
        $(document).ready(function () {
            $('#outstandingTable').DataTable({
                responsive: true,
                ordering: true,
                searching: true,
                pageLength: 10,
                lengthMenu: [[5, 10, 25, 50, 100, -1], [5, 10, 25, 50, 100, "All"]],
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-end"f>>rt<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                columnDefs: [
                    { orderable: false, targets: 3 } // Payment Details column
                ],
                language: {
                    search: "",
                    searchPlaceholder: "Search payments...",
                    lengthMenu: "_MENU_ records per page",
                },
                createdRow: function(row, data, dataIndex) {
                    $(row).find('td').css('vertical-align', 'middle');
                }
            });

            // Custom styling for search box
            $('.dataTables_filter input').addClass('form-control form-control-sm');
        });
    </script>
@endpush
