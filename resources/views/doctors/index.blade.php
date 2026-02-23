@extends('layouts.app')

@section('title')
    Doctors
@endsection

@push('css')
    {{-- DataTables CSS --}}
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        /* Allow dropdown to overflow table container */
        .table-responsive {
            overflow: visible;
        }
    </style>
@endpush

@section('content')
    <x-page-title title="Doctors" subtitle="List Of All Doctors" />

    <div class="row">
        <div class="col-xl-12 mx-auto">
            <div class="card">
                <div class="card-body">

                    <!-- Header with Add New Button -->
                    <div class="d-flex justify-content-between mb-3">
                        <h5 class="mb-0">All Doctors</h5>
                        <a href="{{ route('doctors.create') }}" class="btn btn-primary">Add Doctor</a>
                    </div>

                    <!-- Doctors Table -->
                    <div class="table-responsive">
                        <table id="doctorsTable" class="table table-bordered table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Branch</th>
                                    <th>Phone</th>
                                    <th>Email</th>
                                    <th>Specialization</th>
                                    <th>Status</th>
                                    <th>Shifts</th>
                                    <th style="width:200px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($doctors as $doctor)
                                    <tr>
                                        <td>{{ $doctor->id }}</td>
                                        {{-- Prefix + Full Name --}}
                                        <td>{{ $doctor->prefix . ' ' . $doctor->first_name . ' ' . $doctor->last_name }}</td>
                                        <td>{{ $doctor->branch->name ?? '-' }}</td>
                                        <td>{{ $doctor->phone ?? '-' }}</td>
                                        <td>{{ $doctor->email }}</td>
                                        <td>{{ $doctor->specialization }}</td>
                                        <td>
                                            <span class="badge bg-{{ $doctor->status == 'active' ? 'success' : 'secondary' }}">
                                                {{ ucfirst($doctor->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $doctor->shift == 'morning' ? 'info' : 'warning' }}">
                                                {{ ucfirst($doctor->shift) }}
                                            </span>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-outline-primary">Actions</button>
                                                <button type="button" class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <span class="visually-hidden">Toggle Dropdown</span>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end p-2" style="min-width:220px;">
                                                    <a href="{{ route('doctors.edit', $doctor->id) }}" class="btn btn-sm btn-warning mb-1 w-100">Edit</a>

                                                    <form action="{{ route('doctors.destroy', $doctor->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this doctor?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger mb-1 w-100">Delete</button>
                                                    </form>

                                                    <a href="{{ route('doctors.availability.index', $doctor->id) }}" class="btn btn-sm btn-info mb-1 w-100">Availability</a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No doctors found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <!-- End table -->

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

    {{-- Core Plugins --}}
    <script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/input-tags/js/tagsinput.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/main.js') }}"></script>

    <script>
        $(document).ready(function () {
            $('#doctorsTable').DataTable({
                responsive: true,
                autoWidth: false,
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50, 100],
                ordering: true,
                columnDefs: [
                    { orderable: false, targets: 7 } // Actions column
                ]
            });
        });
    </script>
@endpush
