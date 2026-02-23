@extends('layouts.app')

@section('title')
    Users
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
    <x-page-title title="Users" subtitle="Management" />

    <div class="row">
        <div class="col-xl-12 mx-auto">
            <div class="card">
                <div class="card-body">
                    <!-- Header with Add New Button -->
                    <div class="d-flex justify-content-between mb-3">
                        <h5 class="mb-0">All Users</h5>
                        <a href="{{ route('users.create') }}" class="btn btn-primary">Create New User</a>
                    </div>

                    <!-- Success message -->
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <!-- Users Table -->
                    <div class="table-responsive">
                        <table id="usersTable" class="table table-bordered table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Branch</th>
                                    <th>Role(s)</th>
                                    <th style="width:200px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                    <tr>
                                        <td>{{ $user->id }}</td>
                                        <td>{{ $user->name ?? 'N/A' }}</td>
                                        <td>{{ $user->email ?? 'N/A' }}</td>
                                        <td>{{ $user->branch?->name ?? 'N/A' }}</td>
                                        <td>
                                            @forelse($user->roles as $role)
                                                <span >{{ $role->name }}</span>
                                            @empty
                                                <span class="badge bg-secondary">No Role</span>
                                            @endforelse
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-outline-primary">Actions</button>
                                                <button type="button" class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <span class="visually-hidden">Toggle Dropdown</span>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end p-2" style="min-width:220px;">
                                                    <a href="{{ route('users.edit', $user->id) }}" class="btn btn-sm btn-warning mb-1 w-100">Edit</a>
                                                    <form action="{{ route('users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger mb-1 w-100">Delete</button>
                                                    </form>
                                                    <a href="{{ route('user.permissions.show', $user->id) }}" class="btn btn-sm btn-info w-100">Permissions</a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No users found.</td>
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
    <!-- jQuery first -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <!-- Core Plugins -->
    <script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/input-tags/js/tagsinput.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/main.js') }}"></script>

    <script>
        $(document).ready(function () {
            $('#usersTable').DataTable({
                responsive: true,
                autoWidth: false,
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50, 100],
                ordering: true,
                columnDefs: [
                    { orderable: false, targets: 5 } // Actions column
                ]
            });
        });
    </script>
@endpush
