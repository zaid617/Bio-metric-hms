@extends('layouts.app')

@section('title')
    Doctor Consultations
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
<x-page-title title="Doctor Consultations" subtitle="List of all Doctor consultations" />

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #f8f9fa;">
                <h5 class="mb-0 text-dark">Consultations List</h5>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="sessions-table" class="table table-bordered table-hover">
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
                                <th>Sanction Status</th>
                                <th style="width:200px;">Actions</th>
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
                                    <td>
                                        @php $status = (int)($session->con_status ?? 0); @endphp
                                        @if ($status === 0)
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @elseif($status === 1 || $status === 2)
                                            <span class="badge bg-success">Completed</span>
                                        @elseif($status === 3)
                                            <span class="badge bg-danger">Cancelled</span>
                                        @else
                                            <span class="badge bg-secondary">Unknown</span>
                                        @endif
                                    </td>

                                    {{-- Actions Dropdown --}}
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-outline-primary btn-sm">Actions</button>
                                            <button type="button" class="btn btn-outline-primary btn-sm dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                                                <span class="visually-hidden">Toggle Dropdown</span>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end p-2" style="min-width:180px;">
                                                <a href="{{ route('doctor-consultations.status-view', $session->id) }}" class="btn btn-warning btn-sm mb-1 w-100">Satisfactory Session Update</a>
                                                <a href="{{ route('treatment-sessions.edit', $session->id) }}" class="btn btn-warning btn-sm mb-1 w-100">Edit</a>
                                                <form action="{{ route('treatment-sessions.destroy', $session->id) }}" method="POST" onsubmit="return confirm('Delete this session?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm mb-1 w-100">Delete</button>
                                                </form>
                                                <a href="#" class="btn btn-info btn-sm mb-1 w-100" data-bs-toggle="modal" data-bs-target="#sessionModal{{ $session->id }}">Details</a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                {{-- Session Details Modal --}}
                                <div class="modal fade" id="sessionModal{{ $session->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Session Details (ID: {{ $session->id }})</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <h6 class="text-success">✅ Completed Sessions</h6>
                                                <ul>
                                                    @forelse ($session->sessionTimes->where('is_completed', true) as $entry)
                                                        <li>{{ \Carbon\Carbon::parse($entry->session_datetime)->format('d M Y - h:i A') }}
                                                            - <strong>Doctor:</strong> {{ $entry->doctor?->first_name . ' ' . $entry->doctor?->last_name ?? 'N/A' }},
                                                            <strong>Work:</strong> {{ $entry->work_done ?? 'N/A' }}
                                                        </li>
                                                    @empty
                                                        <li><em>No completed sessions yet</em></li>
                                                    @endforelse
                                                </ul>

                                                <h6 class="text-primary mt-3">🕒 Upcoming Sessions</h6>
                                                <ul>
                                                    @forelse ($session->sessionTimes->where('is_completed', false) as $entry)
                                                        <li>
                                                            {{ \Carbon\Carbon::parse($entry->session_datetime)->format('d M Y - h:i A') }}
                                                            <form action="{{ route('sessions.complete', $entry->id) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                <select name="doctor_id" class="form-control form-control-sm d-inline w-auto" required>
                                                                    @foreach ($doctors as $doctor)
                                                                        <option value="{{ $doctor->id }}" {{ ($entry->completed_by_doctor_id ?? $session->checkup?->doctor?->id) == $doctor->id ? 'selected' : '' }}>
                                                                            {{ $doctor->first_name }} {{ $doctor->last_name }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                                <input type="text" name="work_done" placeholder="Session Description" class="form-control form-control-sm d-inline w-auto" value="{{ $entry->work_done ?? '' }}">
                                                                <button type="submit" class="btn btn-success btn-sm">✔ Complete</button>
                                                            </form>

                                                            <form action="{{ route('sessions.destroy', $entry->id) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete this session?')">❌</button>
                                                            </form>
                                                        </li>
                                                    @empty
                                                        <li><em>No upcoming sessions</em></li>
                                                    @endforelse
                                                </ul>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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

<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
<script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
<script src="{{ URL::asset('build/plugins/input-tags/js/tagsinput.js') }}"></script>
<script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
<script src="{{ URL::asset('build/js/main.js') }}"></script>

<script>
$(document).ready(function () {
    var table = $('#sessions-table').DataTable({
        responsive: true,
        autoWidth: false,
        pageLength: 10,
        lengthMenu: [5,10,25,50,100],
        ordering: true,
        columnDefs: [{ orderable: false, targets: 10 }],
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

    $('.dataTables_filter input').addClass('form-control form-control-sm');
    $('.dataTables_length select').addClass('form-select form-select-sm');
});
</script>
@endpush
