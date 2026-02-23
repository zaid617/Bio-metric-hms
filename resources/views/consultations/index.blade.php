@extends('layouts.app')

@section('title')
    Patient Appointments
@endsection

@push('css')
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">
<style>
.table-responsive {
    overflow: visible; /* Important for dropdown overflow */
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
                    <a href="{{ url('/consultations/create') }}" class="btn btn-primary btn-sm">Add New Consultation</a>
                </div>

                <div class="table-responsive">
                    <table id="consultationsTable" class="table table-bordered table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Patient</th>
                                <th>Date</th>
                                <th>Doctor</th>

                                {{-- Show these columns only if user is NOT doctor --}}
                                @if(!auth()->user()->hasRole('doctor'))
                                <th>Fee</th>
                                <th>Paid Amount</th>
                                <th>Payment Method</th>
                                @endif

                                <th>Checkup Status</th>
                                <th style="width:200px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($consultations as $consultation)
                            <tr>
                                <td>{{ $consultation->patient_name ?? 'N/A' }}</td>
                                <td>{{ \Carbon\Carbon::parse($consultation->created_at)->format('d-m-Y') }}</td>
                                <td>{{ $consultation->doctor_name }}</td>

                                {{-- Show only for non-doctor --}}
                                @if(!auth()->user()->hasRole('doctor'))
                                <td>Rs. {{ number_format($consultation->fee) }}</td>
                                <td>Rs. {{ number_format($consultation->paid_amount) }}</td>
                                <td>{{ bank_get_name($consultation->payment_method) ?? 'N/A' }}</td>
                                @endif

                                <td>
                                    @php $status = (int)($consultation->checkup_status ?? 0); @endphp
                                    @if ($status === 0)
                                    <span class="badge bg-warning text-dark">Pending</span>
                                    @elseif($status === 1)
                                    <span class="badge bg-success">Completed</span>
                                    @elseif($status === 2)
                                    <span class="badge bg-danger">Cancelled</span>
                                    @else
                                    <span class="badge bg-secondary">Unknown</span>
                                    @endif
                                </td>

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
                                            
                                            {{-- History button --}}
                                            <a href="{{ route('consultations.history', $consultation->patient_id) }}"
                                                class="btn btn-dark btn-sm mb-1 w-100">History</a>
                                            
                                            {{-- Print button --}}
                                            <a href="{{ route('consultations.print', $consultation->id) }}"
                                                class="btn btn-secondary btn-sm mb-1 w-100">Print</a>

                                            {{-- Sessions button for doctor/admin --}}
                                            @if(auth()->user()->hasRole(['doctor','admin','receptionist']))
                                            <a href="{{ route('treatment-sessions.create', ['checkup_id' => $consultation->id]) }}"
                                                class="btn btn-success btn-sm mb-1 w-100">Sessions</a>
                                            @endif

                                            {{-- Edit/Delete only for admin --}}
                                            @if(auth()->user()->hasRole('admin'))
                                            <a href="{{ url('/consultations/' . $consultation->id . '/edit') }}"
                                                class="btn btn-warning btn-sm mb-1 w-100">Edit</a>
                                            
                                            <form action="{{ url('/consultations/' . $consultation->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this consultation?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm w-100">Delete</button>
                                            </form>
                                            @endif

                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ auth()->user()->hasRole('doctor') ? 5 : 8 }}" class="text-center">No consultations found.</td>
                            </tr>
                            @endforelse
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
        columnDefs: [{ orderable: false, targets: -1 }] // last column (Actions) not orderable
    });
});
</script>
@endpush
