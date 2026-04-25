@extends('layouts.app')

@section('title')
    Appointment Invoices
@endsection

@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush

@section('content')
    <x-page-title title="Payments" subtitle="Appointment Invoices" />

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                @if(!empty($isSuperAdmin))
                    <div class="col-md-2">
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
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="all" {{ ($filters['status'] ?? 'all') === 'all' ? 'selected' : '' }}>All</option>
                        <option value="outstanding" {{ ($filters['status'] ?? 'all') === 'outstanding' ? 'selected' : '' }}>Outstanding</option>
                        <option value="paid" {{ ($filters['status'] ?? 'all') === 'paid' ? 'selected' : '' }}>Paid</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                </div>

                <div class="col-md-2">
                    <label class="form-label">To</label>
                    <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                </div>

                <div class="col-md-2">
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

    <div class="card">
        <div class="card-body table-responsive">
            <table id="appointmentInvoicesTable" class="table table-striped table-bordered" style="width:100%">
                <thead class="table-dark">
                    <tr>
                        <th>Sr</th>
                        <th>Date</th>
                        <th>Invoice ID</th>
                        @if(!empty($isSuperAdmin))
                            <th>Branch</th>
                        @endif
                        <th>MR</th>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Total</th>
                        <th>Paid</th>
                        <th>Pending</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @php $sr = 1; @endphp
                    @foreach(($appointmentInvoices ?? collect()) as $invoice)
                        @php
                            $pendingAmount = \App\Models\Checkup::calculatePendingAmount(
                                (float) ($invoice->fee ?? 0),
                                (float) ($invoice->discount ?? 0),
                                (float) ($invoice->paid_amount ?? 0)
                            );
                        @endphp
                        <tr>
                            <td>{{ $sr++ }}</td>
                            <td>{{ format_date($invoice->created_at) }}</td>
                            <td>{{ $invoice->id }}</td>
                            @if(!empty($isSuperAdmin))
                                <td>{{ $invoice->branch->name ?? 'N/A' }}</td>
                            @endif
                            <td>{{ $invoice->patient->mr ?? 'N/A' }}</td>
                            <td>{{ $invoice->patient->name ?? 'N/A' }}</td>
                            <td>{{ doctor_get_name($invoice->doctor_id) ?? 'N/A' }}</td>
                            <td>{{ number_format((float) ($invoice->fee ?? 0), 2) }}</td>
                            <td>{{ number_format((float) ($invoice->paid_amount ?? 0), 2) }}</td>
                            <td>{{ number_format((float) $pendingAmount, 2) }}</td>
                            <td>
                                @if((float) $pendingAmount > 0)
                                    <span class="badge bg-danger">Outstanding</span>
                                @else
                                    <span class="badge bg-success">Paid</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('checkups.invoice', $invoice->id) }}" class="btn btn-sm btn-primary">View Invoice</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>

    <script>
        $(document).ready(function () {
            $('#appointmentInvoicesTable').DataTable({
                responsive: true,
                ordering: true,
                searching: true,
                pageLength: 10,
                lengthMenu: [[5, 10, 25, 50, 100, -1], [5, 10, 25, 50, 100, "All"]],
                language: {
                    search: "",
                    searchPlaceholder: "Search invoices...",
                    emptyTable: "No appointment invoices found."
                }
            });
        });
    </script>
@endpush
