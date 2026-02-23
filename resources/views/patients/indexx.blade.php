@extends('layouts.app')

@section('title')
    Patients
@endsection

@push('css')
    {{-- DataTables CSS --}}
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <style>
        /* Dropdown ko table ke bahar overflow allow karne ke liye */
        .table-responsive {
            overflow: visible;
        }
    </style>
@endpush

@section('content')

<x-page-title title="Patients" subtitle="Management" />

<div class="row">
    <div class="col-xl-12 mx-auto">
        <div class="card">
            <div class="card-body">

                {{-- Header --}}
                <div class="d-flex justify-content-between mb-3">
                    <h5 class="mb-0">All Patients</h5>
                    <a href="{{ url('/patients/create') }}" class="btn btn-primary">
                        Add New Patient
                    </a>
                </div>

                {{-- Table --}}
                <div class="table-responsive">
                    <table id="patientsTable" class="table table-bordered table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>MR#</th>
                                <th>Prefix + Name</th>
                                <th>Father / Husband</th>
                                <th>Phone</th>
                                <th>CNIC</th>
                                <th>Gender</th>
                                <th>Age</th>
                                <th>Branch</th>
                                <th style="width:200px;">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($patients as $patient)
                                <tr>
                                    <td>{{ $patient->id }}</td>
                                    <td>{{ ($patient->prefix ? $patient->prefix.' ' : '') . $patient->name }}</td>
                                    <td>{{ $patient->guardian_name }}</td>
                                    <td>{{ $patient->phone }}</td>
                                    <td>{{ $patient->cnic }}</td>
                                    <td>{{ $patient->gender }}</td>
                                    <td>{{ $patient->age }}</td>
                                    <td>{{ $patient->branch?->name ?? 'N/A' }}</td>

                                    {{-- Actions --}}
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-outline-primary">
                                                Actions
                                            </button>
                                            <button type="button"
                                                class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split"
                                                data-bs-toggle="dropdown">
                                            </button>

                                            <div class="dropdown-menu dropdown-menu-end p-2" style="min-width:220px;">

                                                {{-- Print Card --}}
                                                <a href="{{ url('/patients/'.$patient->id) }}"
                                                   class="btn btn-sm btn-info mb-1 w-100">
                                                    Print Card
                                                </a>

                                                {{-- EDIT + DELETE (Admin & Manager only) --}}
                                                @if(auth()->user()->hasAnyRole(['admin', 'manager']))

                                                    <a href="{{ url('/patients/'.$patient->id.'/edit') }}"
                                                       class="btn btn-sm btn-warning mb-1 w-100">
                                                        Edit
                                                    </a>

                                                    <form action="{{ route('patients.destroy', $patient->id) }}"
                                                          method="POST"
                                                          onsubmit="return confirm('Are you sure you want to delete this patient?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                class="btn btn-sm btn-danger mb-1 w-100">
                                                            Delete
                                                        </button>
                                                    </form>

                                                @endif

                                                {{-- Make Appointment --}}
                                               @php
    $role = auth()->user()->getRoleNames()->first(); // admin, manager, receptionist
@endphp

<a href="
    @if($role == 'admin')
        {{ route('admin.appointments.create', ['patient_id' => $patient->id]) }}
    @elseif($role == 'manager')
        {{ route('manager.appointments.create', ['patient_id' => $patient->id]) }}
    @elseif($role == 'receptionist')
        {{ route('receptionist.appointments.create', ['patient_id' => $patient->id]) }}
    @endif
" class="btn btn-sm btn-primary mb-1 w-100">
    Make Appointment
</a>


                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center">
                                        No patients found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{-- End Table --}}

            </div>
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

    {{-- Core Dashboard Plugins --}}
    <script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/input-tags/js/tagsinput.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/main.js') }}"></script>

    {{-- DataTable Init --}}
    <script>
        $(document).ready(function () {
            $('#patientsTable').DataTable({
                responsive: true,
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50, 100],
                ordering: true,
                columnDefs: [
                    { orderable: false, targets: 8 } // Actions column
                ]
            });
        });
    </script>
@endpush
