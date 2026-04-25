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
    <x-page-title title="Payments" subtitle="{{ $subtitle ?? 'Outstanding Payments' }}" />

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                @if(!empty($isSuperAdmin))
                    <div class="col-md-3">
                        <label class="form-label">Branch</label>
                        <select name="branch_id" class="form-select">
                            <option value="">All Branches</option>
                            @foreach(($branches ?? collect()) as $branch)
                                <option value="{{ $branch->id }}" {{ (string)($selectedBranchId ?? '') === (string)$branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-2">
                    <label class="form-label">From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To</label>
                    <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}" placeholder="Invoice ID, patient, MR">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                    <a href="{{ url()->current() }}" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

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
                                @if(!empty($isSuperAdmin))
                                    <th>Branch</th>
                                @endif

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
                            @foreach($outstandings as $session)
                                <tr>
                                    <td>{{ $counter++ }}</td>
                                    <td>{{ format_date($session->created_at) }}</td>
                                    <td>{{ $session->id }}</td>
                                    @if(!empty($isSuperAdmin))
                                        <td>{{ branch_get_name($session->branch_id) ?? 'N/A' }}</td>
                                    @endif
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
                            @endforeach
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
                    emptyTable: "No outstanding payments found.",
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
