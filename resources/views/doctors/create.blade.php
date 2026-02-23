@extends('layouts.app')

@section('title', 'Add Doctor')

@section('content')
<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h3 class="text-white">Add Doctor</h3>
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

            {{-- Add Doctor Form --}}
            <form method="POST" action="{{ route('doctors.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">

                    {{-- Prefix --}}
                    <div class="col-lg-2">
                        <label for="prefix" class="form-label">Prefix</label>
                        <select name="prefix" id="prefix" class="form-control" required>
                            <option value="">Select</option>
                            @foreach(['Mr.', 'Ms.', 'Mrs.'] as $p)
                                <option value="{{ $p }}" {{ old('prefix') == $p ? 'selected' : '' }}>
                                    {{ $p }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- First Name --}}
                    <div class="col-lg-6">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" name="first_name" id="first_name"
                               class="form-control" placeholder="Enter first name"
                               value="{{ old('first_name') }}" required>
                    </div>

                    {{-- Last Name --}}
                    <div class="col-lg-6">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" name="last_name" id="last_name"
                               class="form-control" placeholder="Enter last name"
                               value="{{ old('last_name') }}" required>
                    </div>

                    {{-- Email --}}
                    <div class="col-lg-6">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" name="email" id="email"
                               class="form-control" placeholder="Enter email" required autocomplete="off"
                               value="{{ old('email') }}">
                    </div>

                    {{-- Phone --}}
                    <div class="col-lg-6">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="text" name="phone" id="phone"
                               class="form-control" placeholder="Enter phone number"
                               value="{{ old('phone') }}">
                    </div>

                    {{-- CNIC --}}
                    <div class="col-lg-6">
                        <label for="cnic" class="form-label">CNIC</label>
                        <input type="text" name="cnic" id="cnic"
                               class="form-control" placeholder="Enter CNIC number"
                               value="{{ old('cnic') }}">
                    </div>

                    {{-- Date of Birth --}}
                    <div class="col-lg-6">
                        <label for="dob" class="form-label">Date of Birth</label>
                        <input type="date" name="dob" id="dob" class="form-control"
                               value="{{ old('dob') }}">
                    </div>

                    {{-- Last Education --}}
                    <div class="col-lg-6">
                        <label for="last_education" class="form-label">Last Education / Degree</label>
                        <input type="text" name="last_education" id="last_education"
                               class="form-control" placeholder="Enter last education or degree"
                               value="{{ old('last_education') }}">
                    </div>

                    {{-- Specialization --}}
                    <div class="col-lg-6">
                        <label for="specialization" class="form-label">Specialization</label>
                        <input type="text" name="specialization" id="specialization"
                               class="form-control" placeholder="Enter specialization" required
                               value="{{ old('specialization') }}">
                    </div>

                    {{-- Status --}}
                    <div class="col-lg-6">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-control" required>
                            <option value="">Select Status</option>
                            <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>


       {{-- Shifts --}}
                 <div class="col-lg-6">
    <label for="shift" class="form-label">Shift</label>
    <select name="shift" id="shift" class="form-control" required>
        <option value="">Select Shift</option>
        @foreach(['morning','afternoon','evening'] as $shift)
            <option value="{{ $shift }}" {{ old('shift') == $shift ? 'selected' : '' }}>
                {{ ucfirst($shift) }}
            </option>
        @endforeach
    </select>
</div>




                    {{-- Password --}}
                    <div class="col-lg-6">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" name="password" id="password"
                               class="form-control" placeholder="Enter password" required autocomplete="new-password">
                    </div>

                    {{-- Document Upload --}}
                    <div class="col-lg-6">
                        <label for="document" class="form-label">Upload Document</label>
                        <input type="file" name="document" id="document" class="form-control">
                        <small class="text-muted">Allowed: pdf, doc, docx, jpg, png (max: 2MB)</small>
                    </div>

                    {{-- Picture Upload --}}
                    <div class="col-lg-6">
                        <label for="picture" class="form-label">Upload Picture</label>
                        <input type="file" name="picture" id="picture" class="form-control">
                        <small class="text-muted">Allowed: jpg, jpeg, png (max: 2MB)</small>
                    </div>

                    {{-- Branch --}}
                    <div class="col-lg-6">
                        <label for="branch_id" class="form-label">Branch</label>
                        <select name="branch_id" id="branch_id" class="form-control" required>
                            <option value="">Select Branch</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                </div> {{-- row end --}}

                {{-- Submit Buttons --}}
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Create Doctor</button>
                    <a href="{{ route('doctors.index') }}" class="btn btn-secondary">Back</a>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection

@push('script')
    <!-- Plugins -->
    <script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/select2/js/select2.min.js') }}"></script>

    <!-- Initialize Select2 Dropdowns -->
    <script>
        $(document).ready(function() {
            $('#status').select2({
                placeholder: "Select Status",
                allowClear: true,
                dropdownParent: $('#status').parent()
            });
            $('#branch_id').select2({
                placeholder: "Select Branch",
                allowClear: true,
                dropdownParent: $('#branch_id').parent()
            });

            $('#shift').select2({
                placeholder: "Select Shift",
                allowClear: true,
                dropdownParent: $('#shift').parent()
            });
        });
    </script>

    <!-- Main JS -->
    <script src="{{ URL::asset('build/js/main.js') }}"></script>
@endpush
