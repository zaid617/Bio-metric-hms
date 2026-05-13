@extends('layouts.app')

@section('title', 'Edit Department')

@section('content')
    <x-page-title title="Departments" subtitle="Edit" />

    <div class="row">
        <div class="col-12 col-lg-8 mx-auto">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('departments.update', $department->id) }}" method="POST" class="row g-3">
                        @csrf
                        @method('PUT')

                        <div class="col-12">
                            <label for="name" class="form-label">Department Name</label>
                            <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $department->name) }}" required>
                        </div>

                        <div class="col-12">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" name="status" class="form-select" required>
                                <option value="active" {{ old('status', $department->status) === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $department->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>

                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-warning">Update</button>
                            <a href="{{ route('departments.index') }}" class="btn btn-secondary">Back</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
