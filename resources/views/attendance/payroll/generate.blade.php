@extends('layouts.app')

@section('title')
    Generate Payroll
@endsection

@section('content')
<x-page-title title="Generate Payroll" subtitle="Calculate & Create Salary Records" />

<div class="row">
    <div class="col-xl-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><span class="material-icons-outlined me-2" style="vertical-align:middle">calculate</span>Generate Monthly Payroll</h5>
            </div>
            <div class="card-body">

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show">
                        {{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                @endif

                <form action="{{ route('attendance.payroll.store') }}" method="POST">
                    @csrf

                    <div class="mb-4">
                        <label for="period_start" class="form-label fw-semibold">Payroll Month <span class="text-danger">*</span></label>
                        <input type="month" name="period_start" id="period_start"
                               class="form-control @error('period_start') is-invalid @enderror"
                               value="{{ old('period_start') ? \Carbon\Carbon::parse(old('period_start'))->format('Y-m') : now()->format('Y-m') }}">
                        @error('period_start')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">Select the year and month to generate payroll for.</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Generate For</label>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <input type="radio" class="btn-check" name="generate_type" id="gen_all" value="all" checked>
                                <label class="btn btn-outline-primary w-100" for="gen_all">
                                    <span class="material-icons-outlined d-block mb-1" style="font-size:24px">groups</span>
                                    All Employees
                                </label>
                            </div>
                            <div class="col-md-4">
                                <input type="radio" class="btn-check" name="generate_type" id="gen_branch" value="branch">
                                <label class="btn btn-outline-primary w-100" for="gen_branch">
                                    <span class="material-icons-outlined d-block mb-1" style="font-size:24px">business</span>
                                    By Branch
                                </label>
                            </div>
                            <div class="col-md-4">
                                <input type="radio" class="btn-check" name="generate_type" id="gen_emp" value="employee">
                                <label class="btn btn-outline-primary w-100" for="gen_emp">
                                    <span class="material-icons-outlined d-block mb-1" style="font-size:24px">person</span>
                                    Single Employee
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3" id="branch_field" style="display:none">
                        <label for="branch_id" class="form-label fw-semibold">Select Branch</label>
                        <select name="branch_id" id="branch_id" class="form-select">
                            <option value="">— Choose Branch —</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3" id="employee_field" style="display:none">
                        <label for="employee_id" class="form-label fw-semibold">Select Employee</label>
                        <select name="employee_id" id="employee_id" class="form-select">
                            <option value="">— Choose Employee —</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">
                                    {{ $emp->name }} — {{ $emp->designation }} ({{ $emp->branch->name ?? 'N/A' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="force_regenerate" id="force_regenerate" value="1">
                        <label class="form-check-label" for="force_regenerate">
                            <strong>Force Regenerate</strong>
                            <small class="text-muted d-block">Overwrite existing draft payrolls for the selected period</small>
                        </label>
                    </div>

                    <div class="alert alert-info d-flex gap-2">
                        <span class="material-icons-outlined flex-shrink-0">info</span>
                        <div>
                            Payroll generation automatically calculates:
                            <strong>Basic Salary + Additional Salary + Overtime + Incentives + Awards − Deductions</strong>.
                            Any pre-saved adjustments for the selected period will be applied automatically.
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ route('attendance.payroll.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-5">
                            <span class="material-icons-outlined me-1" style="vertical-align:middle;font-size:18px">play_arrow</span>
                            Generate Payroll
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Convert YYYY-MM (month input) to YYYY-MM-01 before submitting so Laravel's
    // 'date' validation rule accepts it.
    document.querySelector('form').addEventListener('submit', function () {
        var input = document.getElementById('period_start');
        if (input && input.value && input.value.length === 7) {
            input.value = input.value + '-01';
        }
    });

    document.querySelectorAll('input[name="generate_type"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            document.getElementById('branch_field').style.display   = this.value === 'branch'   ? '' : 'none';
            document.getElementById('employee_field').style.display = this.value === 'employee' ? '' : 'none';
            if (this.value !== 'branch')   document.getElementById('branch_id').value   = '';
            if (this.value !== 'employee') document.getElementById('employee_id').value = '';
        });
    });
</script>
@endpush
@endsection
