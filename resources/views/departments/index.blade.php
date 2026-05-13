@extends('layouts.app')

@section('title', 'Departments')

@section('content')
    <x-page-title title="Departments" subtitle="Management" />

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">All Departments</h5>
                        @can('settings.departments.create')
                            <a href="{{ route('departments.create') }}" class="btn btn-primary">Add New Department</a>
                        @endcan
                    </div>

                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Status</th>
                                    <th class="text-center" style="width: 180px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($departments as $department)
                                    <tr>
                                        <td>{{ $department->id }}</td>
                                        <td>{{ $department->name }}</td>
                                        <td>
                                            <span class="badge bg-{{ $department->status === 'active' ? 'success' : 'secondary' }}">
                                                {{ ucfirst($department->status) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-outline-primary">Actions</button>
                                                <button type="button" class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <span class="visually-hidden">Toggle Dropdown</span>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end p-2" style="min-width: 200px;">
                                                    @can('settings.departments.edit')
                                                        <a href="{{ route('departments.edit', $department->id) }}" class="btn btn-sm btn-warning mb-1 w-100">Edit</a>
                                                    @endcan
                                                    @can('settings.departments.delete')
                                                        <form action="{{ route('departments.destroy', $department->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this department?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-danger mb-1 w-100">Delete</button>
                                                        </form>
                                                    @endcan
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No departments found.</td>
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
