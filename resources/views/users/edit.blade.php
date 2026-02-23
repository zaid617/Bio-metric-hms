@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h3>Edit User</h3>
        </div>
        <div class="card-body">

            {{-- Validation Errors --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Edit User Form --}}
            <form action="{{ route('users.update', $user->id) }}" method="POST" class="row g-3">
                @csrf
                @method('PUT')

                {{-- Name --}}
                <div class="col-lg-6">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" name="name" id="name"
                           value="{{ old('name', $user->name) }}"
                           class="form-control" placeholder="Enter name" required>
                </div>

                {{-- Email --}}
                <div class="col-lg-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" name="email" id="email"
                           value="{{ old('email', $user->email) }}"
                           class="form-control" placeholder="Enter email" required>
                </div>

                {{-- Password --}}
                <div class="col-lg-6">
                    <label for="password" class="form-label">Password <small>(Leave blank to keep current)</small></label>
                    <input type="password" name="password" id="password"
                           class="form-control" placeholder="Enter new password if you want to change">
                </div>

                {{-- Branch --}}
                <div class="col-lg-6">
                    <label for="branch_id" class="form-label">Branch</label>
                    <select name="branch_id" id="branch_id" class="form-control" required>
                        <option value="">Select Branch</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('branch_id', $user->branch_id) == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Role --}}
                <div class="col-lg-6">
                    <label for="role" class="form-label">Role</label>
                    <select name="role" id="role" class="form-control" required>
                        <option value="">Select Role</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}" {{ $user->hasRole($role->name) ? 'selected' : '' }}>
                                {{ ucfirst($role->name) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Submit Buttons --}}
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-success">Update User</button>
                    <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancel</a>
                </div>

            </form>

        </div>
    </div>
</div>
@endsection
