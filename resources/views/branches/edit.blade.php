@extends('layouts.app')

@section('title', 'Edit Branch')

@push('css')
    <link href="{{ URL::asset('build/plugins/input-tags/css/tagsinput.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-header bg-warning text-dark">
            <h3>Edit Branch</h3>
        </div>
        <div class="card-body">

            {{-- Validation Errors --}}
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Edit Branch Form --}}
            <form action="{{ route('branches.update', $branch->id) }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Branch Name --}}
                <div class="mb-3">
                    <label for="name" class="form-label">Branch Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $branch->name) }}"
                           class="form-control" placeholder="Enter branch name" required>
                </div>

                {{-- Address --}}
                <div class="mb-3">
                    <label for="address" class="form-label">Address</label>
                    <input type="text" name="address" id="address" value="{{ old('address', $branch->address) }}"
                           class="form-control" placeholder="Enter branch address">
                </div>

                {{-- Phone --}}
                <div class="mb-3">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone', $branch->phone) }}"
                           class="form-control" placeholder="Enter phone number">
                </div>

                {{-- Fee --}}
                <div class="mb-3">
                    <label for="fee" class="form-label">Fee <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="fee" id="fee"
                           value="{{ old('fee', $branch->fee) }}"
                           class="form-control" placeholder="Enter branch fee" required>
                </div>

                {{-- Status --}}
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="active" {{ old('status', $branch->status)=='active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $branch->status)=='inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                {{-- Submit Buttons --}}
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-warning">Update Branch</button>
                    <a href="{{ route('branches.index') }}" class="btn btn-secondary">Back</a>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection

@push('script')
    <script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/input-tags/js/tagsinput.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/main.js') }}"></script>

    <script>
        new PerfectScrollbar('.card-body');
    </script>
@endpush
