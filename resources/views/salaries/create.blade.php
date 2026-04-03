@extends('layouts.app')
@section('title')
    Add Employee Salary
@endsection
@section('content')
    <x-page-title title="Employee Salary" subtitle="Add New Salary Record" />

    <div class="row">
        <div class="col-12 col-lg-12">
            <div class="card">
                <div class="card-body p-4">
                    <h5 class="mb-4">Add Employee Salary</h5>

                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ url('/salaries') }}" method="POST">
                        @csrf
                        
                        <div class="row mb-3">
                            <label for="employee_id" class="col-sm-3 col-form-label">Employee</label>
                            <div class="col-sm-9">
                                <select class="form-select @error('employee_id') is-invalid @enderror" name="employee_id" required>
                                    <option value="">-- Select Employee --</option>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->id }}" {{ (string) old('employee_id') === (string) $employee->id ? 'selected' : '' }}>{{ $employee->name }}</option>
                                    @endforeach
                                </select>
                                @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="month" class="col-sm-3 col-form-label">Salary Date</label>
                            <div class="col-sm-9">
                                <input type="date" class="form-control @error('month') is-invalid @enderror" name="month" required value="{{ old('month', \Carbon\Carbon::now()->format('Y-m-d')) }}">
                                @error('month')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="basic_salary" class="col-sm-3 col-form-label">Basic Salary</label>
                            <div class="col-sm-9">
                                <input type="number" step="0.01" min="0" class="form-control @error('basic_salary') is-invalid @enderror" name="basic_salary" required value="{{ old('basic_salary') }}">
                                @error('basic_salary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="allowances" class="col-sm-3 col-form-label">Allowances</label>
                            <div class="col-sm-9">
                                <input type="number" step="0.01" min="0" class="form-control @error('allowances') is-invalid @enderror" name="allowances" placeholder="0.00" value="{{ old('allowances', 0) }}">
                                @error('allowances')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="bonuses" class="col-sm-3 col-form-label">Bonuses</label>
                            <div class="col-sm-9">
                                <input type="number" step="0.01" min="0" class="form-control @error('bonuses') is-invalid @enderror" name="bonuses" placeholder="0.00" value="{{ old('bonuses', 0) }}">
                                @error('bonuses')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="deductions" class="col-sm-3 col-form-label">Deductions</label>
                            <div class="col-sm-9">
                                <input type="number" step="0.01" min="0" class="form-control @error('deductions') is-invalid @enderror" name="deductions" placeholder="0.00" value="{{ old('deductions', 0) }}">
                                @error('deductions')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="row">
                            <label class="col-sm-3 col-form-label"></label>
                            <div class="col-sm-9">
                                <div class="d-md-flex d-grid align-items-center gap-3">
                                    <button type="submit" class="btn btn-primary px-4">Save</button>
                                    <button type="reset" class="btn btn-secondary px-4">Reset</button>
                                </div>
                            </div>
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
