@extends('layouts.app')

@section('title')
    Add User
@endsection

@push('css')
    <link href="{{ URL::asset('build/plugins/input-tags/css/tagsinput.css') }}" rel="stylesheet">
@endpush

@section('content')
    <x-page-title title="Add New User" subtitle="Management" />

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body">

                    <!-- Form -->
                    <form action="{{ route('users.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Branch</label>
                            <select name="branch_id" class="form-control" required>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>

                       <div class="mb-3">
    <label class="form-label">Login As <span class="text-danger">*</span></label>
    <select name="role" class="form-control" required>
        <option value="">Select Role</option>

        @foreach($roles as $role)
            <option value="{{ $role->name }}">
                {{ ucfirst($role->name) }}
            </option>
        @endforeach
    </select>
</div>


                        <!-- Save + Back Buttons -->
                        <div class="d-flex justify-content-start gap-2 mt-3">
                            <button type="submit" class="btn btn-primary">💾 Save User</button>
                            <a href="{{ route('users.index') }}" class="btn btn-secondary">⬅ Back</a>
                        </div>
                    </form>

                </div>
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
@endpush
