@extends('layouts.app')

@section('title', 'Edit Doctor')

@section('content')
<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h3 class="text-white">Edit Doctor</h3>
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

            {{-- Edit Doctor Form --}}
            <form method="POST" action="{{ route('doctors.update', $doctor->id) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row g-3">

                    {{-- Prefix --}}
                    @php $currentPrefix = old('prefix') ?? $doctor->prefix; @endphp
                    <div class="col-lg-2">
                        <label for="prefix" class="form-label">Prefix</label>
                        <select name="prefix" id="prefix" class="form-control" required>
                            <option value="">Select</option>
                            @foreach(['Mr.', 'Ms.', 'Mrs.'] as $p)
                                <option value="{{ $p }}" {{ $currentPrefix == $p ? 'selected' : '' }}>
                                    {{ $p }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- First Name --}}
                    <div class="col-lg-5">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" name="first_name" id="first_name"
                               value="{{ old('first_name', $doctor->first_name) }}"
                               class="form-control" placeholder="Enter first name" required>
                    </div>

                    {{-- Last Name --}}
                    <div class="col-lg-5">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" name="last_name" id="last_name"
                               value="{{ old('last_name', $doctor->last_name) }}"
                               class="form-control" placeholder="Enter last name" required>
                    </div>

                    {{-- Email --}}
                    <div class="col-lg-6">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" name="email" id="email"
                               value="{{ old('email', $doctor->email) }}"
                               class="form-control" placeholder="Enter email" required>
                    </div>

                    {{-- Password (Optional) --}}
                    <div class="col-lg-6">
                        <label for="password" class="form-label">Password <small>(Leave blank to keep current)</small></label>
                        <input type="password" name="password" id="password"
                               class="form-control" placeholder="Enter new password if you want to change">
                    </div>

                    {{-- Phone --}}
                    <div class="col-lg-6">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="text" name="phone" id="phone"
                               value="{{ old('phone', $doctor->phone) }}"
                               class="form-control" placeholder="Enter phone number">
                    </div>

                    {{-- Specialization --}}
                    <div class="col-lg-6">
                        <label for="specialization" class="form-label">Specialization</label>
                        <input type="text" name="specialization" id="specialization"
                               value="{{ old('specialization', $doctor->specialization) }}"
                               class="form-control" placeholder="Enter specialization" required>
                    </div>

                    {{-- Branch --}}
                    @php $currentBranch = old('branch_id') ?? $doctor->branch_id; @endphp
                    <div class="col-lg-6">
                        <label for="branch_id" class="form-label">Branch</label>
                        <select name="branch_id" id="branch_id" class="form-control" required>
                            <option value="">Select Branch</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ $currentBranch == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- CNIC --}}
                    <div class="col-lg-6">
                        <label for="cnic" class="form-label">CNIC</label>
                        <input type="text" name="cnic" id="cnic"
                               value="{{ old('cnic', $doctor->cnic) }}"
                               class="form-control" placeholder="Enter CNIC">
                    </div>

                    {{-- DOB --}}
                    <div class="col-lg-6">
                        <label for="dob" class="form-label">Date of Birth</label>
                        <input type="date" name="dob" id="dob"
                               value="{{ old('dob', $doctor->dob) }}"
                               class="form-control">
                    </div>

                    {{-- Last Education --}}
                    <div class="col-lg-6">
                        <label for="last_education" class="form-label">Last Education / Degree</label>
                        <input type="text" name="last_education" id="last_education"
                               value="{{ old('last_education', $doctor->last_education) }}"
                               class="form-control" placeholder="Enter last education or degree">
                    </div>

                    {{-- Status --}}
                    @php $currentStatus = old('status') ?? $doctor->status; @endphp
                    <div class="col-lg-6">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-control" required>
                            <option value="">Select Status</option>
                            <option value="active" {{ $currentStatus == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ $currentStatus == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>

                    {{-- Shift --}}
                    @php $currentShift = strtolower(old('shift', $doctor->shift)); @endphp
                    <div class="col-lg-6">
                        <label for="shift" class="form-label">Shift</label>
                        <select name="shift" id="shift" class="form-control" required>
                            <option value="">Select Shift</option>
                            @foreach(['morning','afternoon','evening'] as $shift)
                                <option value="{{ $shift }}" {{ $currentShift == $shift ? 'selected' : '' }}>
                                    {{ ucfirst($shift) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Document Upload --}}
                    <div class="col-lg-6">
                        <label for="document" class="form-label">Upload Document</label>
                        <input type="file" name="document" id="document" class="form-control">
                        @if($doctor->document)
                            <a href="{{ asset('storage/' . $doctor->document) }}" target="_blank" class="mt-1 d-block">View Current Document</a>
                        @endif
                    </div>

                    {{-- Picture Upload --}}
                    <div class="col-lg-6">
                        <label for="picture" class="form-label">Upload Picture</label>
                        <input type="file" name="picture" id="picture" class="form-control">
                        @if($doctor->picture)
                            <img src="{{ asset('storage/' . $doctor->picture) }}" alt="Doctor Picture"
                                 class="img-thumbnail mt-2" width="120">
                        @endif
                    </div>

                </div> {{-- row end --}}

                {{-- Submit Buttons --}}
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-success">Update Doctor</button>
                    <a href="{{ route('doctors.index') }}" class="btn btn-secondary">Cancel</a>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection
