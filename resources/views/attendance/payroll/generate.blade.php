@extends('layouts.app')

@section('title')
    Generate Payroll
@endsection

@section('content')
    <x-page-title title="Generate Payroll" subtitle="Salary Calculation" />

    <div class="row">
        <div class="col-xl-8 mx-auto">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('attendance.payroll.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="period_start" class="form-label">Payroll Month *</label>
                            <input type="date" name="period_start" id="period_start"
                                   class="form-control @error('period_start') is-invalid @enderror"
                                   value="{{ old('period_start') }}" required>
                            @error('period_start')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Use any date in the target month. Payroll runs for the full month.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Generate For</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="generate_type"
                                       id="generate_all" value="all" checked onchange="toggleFields()">
                                <label class="form-check-label" for="generate_all">
                                    All Branches (All Employees)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="generate_type"
                                       id="generate_branch" value="branch" onchange="toggleFields()">
                                <label class="form-check-label" for="generate_branch">
                                    Specific Branch
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="generate_type"
                                       id="generate_employee" value="employee" onchange="toggleFields()">
                                <label class="form-check-label" for="generate_employee">
                                    Specific Employee
                                </label>
                            </div>
                        </div>

                        <div class="mb-3" id="branch_field" style="display:none;">
                            <label for="branch_id" class="form-label">Select Branch</label>
                            <select name="branch_id" id="branch_id" class="form-select">
                                <option value="">Select Branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3" id="employee_field" style="display:none;">
                            <label for="employee_id" class="form-label">Select Employee</label>
                            <select name="employee_id" id="employee_id" class="form-select">
                                <option value="">Select Employee</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}">
                                        {{ $employee->name }} - {{ $employee->designation }} ({{ $employee->branch->name ?? 'N/A' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="alert alert-info">
                            <i class="material-icons-outlined">info</i>
                            <strong>Note:</strong> Generation includes attendance deductions, commissions, incentives, awards, and custom adjustments.
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" value="1" id="force_regenerate" name="force_regenerate">
                            <label class="form-check-label" for="force_regenerate">
                                Regenerate existing payroll records for the selected month
                            </label>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('attendance.payroll.index') }}" class="btn btn-secondary">
                                 Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                 Generate Payroll
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        function toggleFields() {
            const type = document.querySelector('input[name="generate_type"]:checked').value;

            document.getElementById('branch_field').style.display = 'none';
            document.getElementById('employee_field').style.display = 'none';

            document.getElementById('branch_id').removeAttribute('required');
            document.getElementById('employee_id').removeAttribute('required');

            if (type === 'branch') {
                document.getElementById('branch_field').style.display = 'block';
                document.getElementById('branch_id').setAttribute('required', 'required');
            } else if (type === 'employee') {
                document.getElementById('employee_field').style.display = 'block';
                document.getElementById('employee_id').setAttribute('required', 'required');
            }
        }
    </script>
@endpush
