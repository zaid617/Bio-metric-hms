@extends('layouts.app')
@section('title', 'Add New Employee')

@section('content')
<x-page-title title="Employee" subtitle="Add New" />

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm rounded-4 border-0">
            <div class="card-body p-5">
                <h3 class="mb-4 text-primary fw-bold">Employee Information</h3>

                {{-- Success Message --}}
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form action="{{ url('/employees') }}" method="POST" class="row g-4">
                    @csrf


                    {{-- Prefix --}}
<div class="col-md-2">
    <label for="prefix" class="form-label fw-semibold">Prefix</label>
    <select class="form-select form-select-lg" id="prefix" name="prefix" required>
        <option value="">Select</option>
        <option value="Mr.">Mr.</option>
        <option value="Ms.">Ms.</option>
        <option value="Mrs.">Mrs.</option>
    </select>
</div>

                    {{-- Name --}}
                   <div class="col-md-4">

                        <label for="name" class="form-label fw-semibold">Name</label>
                        <input type="text" class="form-control form-control-lg" id="name" name="name" placeholder="Employee Name" required>
                    </div>

                    {{-- Designation --}}
                    <div class="col-md-6">
                        <label for="designation" class="form-label fw-semibold">Designation</label>
                        <input type="text" class="form-control form-control-lg" id="designation" name="designation" placeholder="Designation" required>
                    </div>

                    {{-- Branch --}}
                    <div class="col-md-6">
                        <label for="branch_id" class="form-label fw-semibold">Branch</label>
                        <select class="form-select form-select-lg" id="branch_id" name="branch_id" required>
                            <option value="">Select Branch</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Department --}}
                    <div class="col-md-6">
                        <label for="department" class="form-label fw-semibold">Department</label>
                        <select class="form-select form-select-lg" id="department" name="department" required>
                            <option value="">Select Department</option>
                            <option value="Male Physiotherapy Department">Male Physiotherapy Department</option>
                            <option value="Female Physiotherapy Department">Female Physiotherapy Department</option>
                            <option value="Paeds Physiotherapy Department">Paeds Physiotherapy Department</option>
                            <option value="Speech Therapy Department">Speech Therapy Department</option>
                            <option value="Behavior Therapy Department">Behavior Therapy Department</option>
                            <option value="Occupational Therapy Department">Occupational Therapy Department</option>
                            <option value="Remedial Therapy Department">Remedial Therapy Department</option>
                            <option value="Clinical Psychology Department">Clinical Psychology Department</option>
                        </select>
                    </div>

                    {{-- Shift --}}
                    <div class="col-md-6">
                        <label for="shift" class="form-label fw-semibold">Shift</label>
                        <select class="form-select form-select-lg" id="shift" name="shift" required>
                            <option value="">Select Shift</option>
                            <option value="Morning">Morning</option>
                            <option value="Afternoon">Afternoon</option>
                            <option value="Evening">Evening</option>
                        </select>
                    </div>

                    {{-- Basic Salary --}}
                    <div class="col-md-6">
                        <label for="basic_salary" class="form-label fw-semibold">Basic Salary</label>
                        <input type="text" class="form-control form-control-lg" id="basic_salary" name="basic_salary" placeholder="Basic Salary" required>
                    </div>

                    {{-- Phone --}}
                    <div class="col-md-6">
                        <label for="phone" class="form-label fw-semibold">Phone</label>
                        <input type="text" class="form-control form-control-lg" id="phone" name="phone" placeholder="Phone Number" required>
                    </div>

                    {{-- Joining Date --}}
                    <div class="col-md-6">
                        <label for="joining_date" class="form-label fw-semibold">Joining Date</label>
                        <input type="date" class="form-control form-control-lg" id="joining_date" name="joining_date" required>
                    </div>

                    {{-- Buttons --}}
                    <div class="col-12 mt-4 d-flex gap-3">
                        <button type="submit" class="btn btn-primary btn-lg px-5" style="background: linear-gradient(135deg, #1E90FF, #00BFFF); border:none;">
                            <i class="bi bi-save me-2"></i> Save Employee
                        </button>
                        <button type="reset" class="btn btn-outline-secondary btn-lg px-5">
                            <i class="bi bi-x-circle me-2"></i> Reset
                        </button>
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
<script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
<script src="{{ URL::asset('build/js/main.js') }}"></script>
@endpush
