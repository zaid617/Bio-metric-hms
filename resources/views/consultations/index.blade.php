@extends('layouts.app')

@section('title')
    Patient Appointments
@endsection

@push('css')
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">
<style>
.table-responsive {
     overflow-x: auto;
    overflow-y: visible;
}
</style>
@endpush

@section('content')
<x-page-title title="Patient Records" subtitle="Appointments List" />

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <h5 class="mb-0">Appointments List</h5>
                    @can('appointments.book')
                    <a href="{{ url('/consultations/create') }}" class="btn btn-primary btn-sm">Add New Consultation</a>
                    @endcan
                </div>

                <form method="GET" action="{{ route('consultations.index') }}" class="row g-2 mb-3">
                    <div class="col-md-2">
                        <label for="consultation_type" class="form-label">Consultation Type</label>
                        <select name="consultation_type" id="consultation_type" class="form-select form-select-sm">
                            <option value="">All</option>
                            @foreach(($consultationTypes ?? ['Appointment', 'Enrollment']) as $type)
                                <option value="{{ $type }}" {{ (($filters['consultation_type'] ?? '') === $type) ? 'selected' : '' }}>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="date_from" class="form-label">From</label>
                        <input type="date" name="date_from" id="date_from" class="form-control form-control-sm" value="{{ $filters['date_from'] ?? '' }}">
                    </div>

                    <div class="col-md-2">
                        <label for="date_to" class="form-label">To</label>
                        <input type="date" name="date_to" id="date_to" class="form-control form-control-sm" value="{{ $filters['date_to'] ?? '' }}">
                    </div>

                    <div class="col-md-2">
                        <label for="payment_status" class="form-label">Payment Status</label>
                        <select name="payment_status" id="payment_status" class="form-select form-select-sm">
                            <option value="">All</option>
                            @foreach(($paymentStatusOptions ?? []) as $statusKey => $statusLabel)
                                <option value="{{ $statusKey }}" {{ (($filters['payment_status'] ?? '') === $statusKey) ? 'selected' : '' }}>{{ $statusLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="doctor_id" class="form-label">Doctor/Provider</label>
                        <select name="doctor_id" id="doctor_id" class="form-select form-select-sm">
                            <option value="">All</option>
                            @foreach(($doctors ?? []) as $doctor)
                                <option value="{{ $doctor->id }}" {{ (string) ($filters['doctor_id'] ?? '') === (string) $doctor->id ? 'selected' : '' }}>
                                    {{ $doctor->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="patient_search" class="form-label">Patient Name / ID</label>
                        <input type="text" name="patient_search" id="patient_search" class="form-control form-control-sm" placeholder="Name, MR, ID"
                               value="{{ $filters['patient_search'] ?? '' }}">
                    </div>

                    <div class="col-md-12 d-flex gap-2 mt-2">
                        <button type="submit" class="btn btn-sm btn-primary">Apply Filters</button>
                        <a href="{{ route('consultations.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table id="consultationsTable" class="table table-bordered table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Patient</th>
                                <th>MR/ID</th>
                                <th>Doctor</th>
                                <th>Created By</th>
                                <th>Created Date</th>
                                <th>Updated Date</th>
                                <th>Updated By</th>
                                <th>Consultation Type</th>

                                {{-- Show these columns only if user is NOT doctor --}}
                                @if(!auth()->user()->hasRole('doctor'))
                                <th>Fee</th>
                                <th>Discount (%)</th>
                                <th>Paid Amount</th>
                                <th>Pending Amount</th>
                                <th>Payment Status</th>
                                <th>Referred By</th>
                                <th>Payment Method</th>
                                @endif
                                <th style="width:200px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($consultations as $consultation)
                            @php
                                $pendingAmount = (float) ($consultation->pending_amount_resolved ?? $consultation->pending_amount ?? 0);
                                $paidAmount = (float) ($consultation->paid_amount ?? 0);
                                $displayDate = $consultation->checkup_date ?? $consultation->created_at;
                            @endphp
                            <tr>
                                <td>{{ $consultation->patient_name ?? 'N/A' }}</td>
                                <td>{{ $consultation->mr ?? $consultation->patient_id ?? 'N/A' }}</td>
                                <td>{{ $consultation->doctor_name }}</td>
                                <td>{{ $consultation->created_by_name ?? 'N/A' }}</td>
                                <td>{{ !empty($consultation->created_at) ? format_date($consultation->created_at) : 'N/A' }}</td>
                                <td>{{ !empty($consultation->updated_at) ? format_date($consultation->updated_at) : 'N/A' }}</td>
                                <td>{{ $consultation->updated_by_name ?? 'N/A' }}</td>
                                <td>{{ $consultation->consultation_type_display ?? ($consultation->consultation_type ?? 'Appointment') }}</td>

                                {{-- Show only for non-doctor --}}
                                @if(!auth()->user()->hasRole('doctor'))
                                <td>{{ number_format((float) ($consultation->fee ?? 0), 2) }}</td>
                                <td>{{ number_format((float) ($consultation->discount ?? 0), 2) }}%</td>
                                <td>{{ number_format($paidAmount, 2) }}</td>
                                <td>{{ number_format($pendingAmount, 2) }}</td>
                                <td>
                                    @if($pendingAmount <= 0)
                                        <span class="badge bg-success">Fully Paid</span>
                                    @elseif($paidAmount > 0)
                                        <span class="badge bg-warning text-dark">Partially Paid</span>
                                    @else
                                        <span class="badge bg-danger">Unpaid</span>
                                    @endif
                                </td>
                                <td>{{ $consultation->referred_by_name ?? 'N/A' }}</td>
                                <td>{{ bank_get_name($consultation->payment_method) ?? 'N/A' }}</td>
                                @endif

                                <td class="text-center">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-outline-primary btn-sm">Actions</button>
                                        <button type="button"
                                            class="btn btn-outline-primary btn-sm dropdown-toggle dropdown-toggle-split"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <span class="visually-hidden">Toggle Dropdown</span>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end p-2" style="min-width:180px;">

                                            {{-- View button --}}
                                            <a href="{{ url('/consultations/' . $consultation->id) }}"
                                                class="btn btn-info btn-sm mb-1 w-100">View</a>

                                            {{-- History --}}
                                            @can('appointments.history')
                                            <a href="{{ route('consultations.history', $consultation->patient_id) }}"
                                                class="btn btn-dark btn-sm mb-1 w-100">History</a>
                                            @endcan

                                            {{-- Print --}}
                                            @can('appointments.print')
                                            <a href="{{ route('consultations.print', $consultation->id) }}"
                                                class="btn btn-secondary btn-sm mb-1 w-100">Print</a>
                                            @endcan

                                            {{-- Invoice --}}
                                            @can('appointments.invoice')
                                            <a href="{{ route('checkups.invoice', $consultation->id) }}"
                                                class="btn btn-outline-secondary btn-sm mb-1 w-100">Invoice</a>
                                            @endcan

                                            {{-- Sessions --}}
                                            @can('appointments.sessions')
                                            <a href="{{ route('treatment-sessions.create', ['checkup_id' => $consultation->id]) }}"
                                                class="btn btn-success btn-sm mb-1 w-100">Sessions</a>
                                            @endcan

                                            {{-- Edit --}}
                                            @can('appointments.edit')
                                            <a href="{{ url('/consultations/' . $consultation->id . '/edit') }}"
                                                class="btn btn-warning btn-sm mb-1 w-100">Edit</a>
                                            @endcan

                                            {{-- Delete --}}
                                            @can('appointments.delete')
                                            <form action="{{ url('/consultations/' . $consultation->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this consultation?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm w-100">Delete</button>
                                            </form>
                                            @endcan

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

{{-- Required Plugins --}}
<script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
<script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
<script src="{{ URL::asset('build/plugins/input-tags/js/tagsinput.js') }}"></script>
<script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
<script src="{{ URL::asset('build/js/main.js') }}"></script>

<script>
$(document).ready(function () {
    $('#consultationsTable').DataTable({
        responsive: true,
        autoWidth: false,
        pageLength: 10,
        lengthMenu: [5,10,25,50,100],
        ordering: true,
        language: {
            emptyTable: 'No consultations found.'
        },
        columnDefs: [{ orderable: false, targets: -1 }]
    });
});
</script>
@endpush
