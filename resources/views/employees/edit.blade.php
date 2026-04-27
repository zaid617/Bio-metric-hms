@extends('layouts.app')

@section('title', 'Edit Employee')

@section('content')
<x-page-title title="Employee" subtitle="Edit Employee" />

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm rounded-4 border-0">
            <div class="card-body p-5">
                <h3 class="mb-4 text-primary fw-bold">Edit Employee Information</h3>

                {{-- Success Message --}}
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form action="{{ url('/employees/'.$employee->id) }}" method="POST" class="row g-4">
                    @csrf
                    @method('PUT')
                    @php
                        $storedOtherAllowances = [];
                        if (!empty($employee->other_allowances)) {
                            if (is_string($employee->other_allowances)) {
                                $storedOtherAllowances = json_decode($employee->other_allowances, true) ?: [];
                            } elseif (is_array($employee->other_allowances)) {
                                $storedOtherAllowances = $employee->other_allowances;
                            }
                        }

                        $storedOtherAllowancesTotal = 0;
                        foreach ($storedOtherAllowances as $row) {
                            $storedOtherAllowancesTotal += (float) ($row['amount'] ?? 0);
                        }

                        $legacyOtherAllowance = (float)($employee->other_allowance ?? 0);
                        $effectiveOtherAllowance = $storedOtherAllowancesTotal > 0
                            ? $storedOtherAllowancesTotal
                            : $legacyOtherAllowance;

                        $hasIncentiveComponents = (float)($employee->incentive_sunday_roster ?? 0)
                            + (float)($employee->incentive_home_visit ?? 0)
                            + (float)($employee->incentive_speech_therapy ?? 0)
                            + (float)($employee->incentive_dry_needling ?? 0) > 0;
                        $hasSalaryComponents = (float)($employee->allowance_allied_health_council ?? 0)
                            + (float)($employee->allowance_house_job ?? 0)
                            + (float)($employee->allowance_conveyance ?? 0)
                            + (float)($employee->allowance_medical ?? 0)
                            + (float)($employee->allowance_house_rent ?? 0)
                            + (float)($employee->allowance_branch_manager ?? 0)
                            + (float)($employee->allowance_assistant_branch_manager ?? 0)
                            + $effectiveOtherAllowance > 0
                            || $hasIncentiveComponents;
                    @endphp

                    {{-- Prefix --}}
                    <div class="col-md-2">
                        <label for="prefix" class="form-label fw-semibold">Prefix</label>
                        <select class="form-select form-select-lg" id="prefix" name="prefix" required>
                            <option value="">Select</option>
                            @foreach(['Mr.', 'Ms.', 'Mrs.'] as $p)
                                <option value="{{ $p }}" {{ old('prefix', $employee->prefix) == $p ? 'selected' : '' }}>
                                    {{ $p }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Name --}}
                    <div class="col-md-6">
                        <label for="name" class="form-label fw-semibold">Name</label>
                        <input type="text" class="form-control form-control-lg" id="name" name="name"
                               value="{{ old('name', $employee->name) }}" required>
                    </div>

                    {{-- Designation --}}
                    <div class="col-md-6">
                        <label for="designation" class="form-label fw-semibold">Designation</label>
                        <select class="form-select form-select-lg" id="designation" name="designation" required>
                            <option value="Employee" {{ old('designation', $employee->designation) === 'Employee' ? 'selected' : '' }}>Employee</option>
                            <option value="Doctor" {{ old('designation', $employee->designation) === 'Doctor' ? 'selected' : '' }}>Doctor</option>
                        </select>
                    </div>

                    {{-- Branch --}}
                    <div class="col-md-6">
                        <label for="branch_id" class="form-label fw-semibold">Branch</label>
                        <select class="form-select form-select-lg" id="branch_id" name="branch_id" required>
                            <option value="">Select Branch</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}"
                                    {{ old('branch_id', $employee->branch_id) == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Department --}}
                    <div class="col-md-6">
                        <label for="department" class="form-label fw-semibold">Department</label>
                        <select class="form-select form-select-lg" id="department" name="department" required>
                            <option value="">Select Department</option>
                            @foreach ([
                                'Male Physiotherapy Department',
                                'Female Physiotherapy Department',
                                'Paeds Physiotherapy Department',
                                'Speech Therapy Department',
                                'Behavior Therapy Department',
                                'Occupational Therapy Department',
                                'Remedial Therapy Department',
                                'Clinical Psychology Department'
                            ] as $dept)
                                <option value="{{ $dept }}" {{ old('department', $employee->department) == $dept ? 'selected' : '' }}>
                                    {{ $dept }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Shift --}}
                    <div class="col-md-6">
                        <label for="shift" class="form-label fw-semibold">Shift</label>
                        <select class="form-select form-select-lg" id="shift" name="shift" required>
                            <option value="">Select Shift</option>
                            @foreach(['Morning','Afternoon','Evening'] as $shift)
                                <option value="{{ $shift }}" {{ old('shift', $employee->shift) == $shift ? 'selected' : '' }}>
                                    {{ $shift }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Shift Start Time --}}
                    <div class="col-md-6">
                        <label for="shift_start_time" class="form-label fw-semibold">Shift Start Time</label>
                        <input type="time" class="form-control form-control-lg" id="shift_start_time" name="shift_start_time"
                               value="{{ old('shift_start_time', $employee->shift_start_time ? substr($employee->shift_start_time, 0, 5) : config('payroll.shift_start', '09:00')) }}"
                               required>
                    </div>

                    {{-- Basic Salary --}}
                    <div class="col-md-6">
                        <label for="basic_salary" class="form-label fw-semibold">Basic Salary</label>
                           <input type="text" class="form-control form-control-lg js-money-format" id="basic_salary" name="basic_salary"
                               value="{{ old('basic_salary', number_format($employee->basic_salary, 2, '.', '')) }}" required>
                           @error('basic_salary')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>

                    {{-- Working Hours --}}
                    <div class="col-md-6">
                        <label for="working_hours" class="form-label fw-semibold">Working Hours / Day</label>
                        <input type="number" class="form-control form-control-lg" id="working_hours" name="working_hours"
                               placeholder="e.g. 8" min="1" max="24" step="0.5"
                               value="{{ old('working_hours', $employee->working_hours) }}" required>
                    </div>

                    <div class="col-12">
                        <div class="card border mt-2">
                            <div class="card-header bg-light d-flex align-items-center justify-content-between">
                                <h6 class="mb-0 fw-bold">Salary Components</h6>
                                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#salaryComponentsEdit" aria-expanded="{{ $hasSalaryComponents ? 'true' : 'false' }}" aria-controls="salaryComponentsEdit">
                                    {{ $hasSalaryComponents ? 'Hide Components' : 'Show Components' }}
                                </button>
                            </div>
                            <div class="collapse {{ $hasSalaryComponents ? 'show' : '' }}" id="salaryComponentsEdit">
                                <div class="card-body">
                                    <div class="mb-3">
                                        <h6 class="text-primary mb-3">Allowances</h6>
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label for="allowance_allied_health_council" class="form-label">Allied Health Council</label>
                                                <input type="number" step="0.01" min="0" id="allowance_allied_health_council" name="allowance_allied_health_council" class="form-control salary-component" placeholder="0.00" value="{{ old('allowance_allied_health_council', $employee->allowance_allied_health_council ?? '0.00') }}">
                                                @error('allowance_allied_health_council')<small class="text-danger">{{ $message }}</small>@enderror
                                            </div>
                                            <div class="col-md-4">
                                                <label for="allowance_house_job" class="form-label">House Job</label>
                                                <input type="number" step="0.01" min="0" id="allowance_house_job" name="allowance_house_job" class="form-control salary-component" placeholder="0.00" value="{{ old('allowance_house_job', $employee->allowance_house_job ?? '0.00') }}">
                                                @error('allowance_house_job')<small class="text-danger">{{ $message }}</small>@enderror
                                            </div>
                                            <div class="col-md-4">
                                                <label for="allowance_conveyance" class="form-label">Conveyance</label>
                                                <input type="number" step="0.01" min="0" id="allowance_conveyance" name="allowance_conveyance" class="form-control salary-component" placeholder="0.00" value="{{ old('allowance_conveyance', $employee->allowance_conveyance ?? '0.00') }}">
                                                @error('allowance_conveyance')<small class="text-danger">{{ $message }}</small>@enderror
                                            </div>
                                            <div class="col-md-4">
                                                <label for="allowance_medical" class="form-label">Medical</label>
                                                <input type="number" step="0.01" min="0" id="allowance_medical" name="allowance_medical" class="form-control salary-component" placeholder="0.00" value="{{ old('allowance_medical', $employee->allowance_medical ?? '0.00') }}">
                                                @error('allowance_medical')<small class="text-danger">{{ $message }}</small>@enderror
                                            </div>
                                            <div class="col-md-4">
                                                <label for="allowance_house_rent" class="form-label">House Rent Allowance</label>
                                                <input type="number" step="0.01" min="0" id="allowance_house_rent" name="allowance_house_rent" class="form-control salary-component" placeholder="0.00" value="{{ old('allowance_house_rent', $employee->allowance_house_rent ?? '0.00') }}">
                                                @error('allowance_house_rent')<small class="text-danger">{{ $message }}</small>@enderror
                                            </div>
                                            <div class="col-md-4">
                                                <label for="allowance_branch_manager" class="form-label">Branch Manager Allowance</label>
                                                <input type="number" step="0.01" min="0" id="allowance_branch_manager" name="allowance_branch_manager" class="form-control salary-component" placeholder="0.00" value="{{ old('allowance_branch_manager', $employee->allowance_branch_manager ?? '0.00') }}">
                                                @error('allowance_branch_manager')<small class="text-danger">{{ $message }}</small>@enderror
                                            </div>
                                            <div class="col-md-4">
                                                <label for="allowance_assistant_branch_manager" class="form-label">Assistant Branch Manager Allowance</label>
                                                <input type="number" step="0.01" min="0" id="allowance_assistant_branch_manager" name="allowance_assistant_branch_manager" class="form-control salary-component" placeholder="0.00" value="{{ old('allowance_assistant_branch_manager', $employee->allowance_assistant_branch_manager ?? '0.00') }}">
                                                @error('allowance_assistant_branch_manager')<small class="text-danger">{{ $message }}</small>@enderror
                                            </div>
                                        </div>
                                    </div>

                                    {{-- <div class="mb-3">
                                        <h6 class="text-primary mb-3">Incentives</h6>
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <label for="incentive_sunday_roster" class="form-label">Sunday Roster</label>
                                                <input type="number" step="0.01" min="0" id="incentive_sunday_roster" name="incentive_sunday_roster" class="form-control salary-component" placeholder="0.00" value="{{ old('incentive_sunday_roster', $employee->incentive_sunday_roster ?? '0.00') }}">
                                                @error('incentive_sunday_roster')<small class="text-danger">{{ $message }}</small>@enderror
                                            </div>
                                            <div class="col-md-3">
                                                <label for="incentive_home_visit" class="form-label">Home Visit</label>
                                                <input type="number" step="0.01" min="0" id="incentive_home_visit" name="incentive_home_visit" class="form-control salary-component" placeholder="0.00" value="{{ old('incentive_home_visit', $employee->incentive_home_visit ?? '0.00') }}">
                                                @error('incentive_home_visit')<small class="text-danger">{{ $message }}</small>@enderror
                                            </div>
                                            <div class="col-md-3">
                                                <label for="incentive_speech_therapy" class="form-label">Speech Therapy</label>
                                                <input type="number" step="0.01" min="0" id="incentive_speech_therapy" name="incentive_speech_therapy" class="form-control salary-component" placeholder="0.00" value="{{ old('incentive_speech_therapy', $employee->incentive_speech_therapy ?? '0.00') }}">
                                                @error('incentive_speech_therapy')<small class="text-danger">{{ $message }}</small>@enderror
                                            </div>
                                            <div class="col-md-3">
                                                <label for="incentive_dry_needling" class="form-label">Dry Needling</label>
                                                <input type="number" step="0.01" min="0" id="incentive_dry_needling" name="incentive_dry_needling" class="form-control salary-component" placeholder="0.00" value="{{ old('incentive_dry_needling', $employee->incentive_dry_needling ?? '0.00') }}">
                                                @error('incentive_dry_needling')<small class="text-danger">{{ $message }}</small>@enderror
                                            </div>
                                        </div>
                                    </div> --}}

                                    <div class="mb-3">
                                        <h6 class="text-primary mb-3">Other</h6>
                                        @php
                                            $otherAllowanceRows = [];
                                            $otherAllowanceLabels = old('other_allowances.labels');
                                            $otherAllowanceAmounts = old('other_allowances.amounts');

                                            if (is_array($otherAllowanceLabels) || is_array($otherAllowanceAmounts)) {
                                                $otherAllowanceLabels = is_array($otherAllowanceLabels) ? $otherAllowanceLabels : [];
                                                $otherAllowanceAmounts = is_array($otherAllowanceAmounts) ? $otherAllowanceAmounts : [];
                                                $otherAllowanceCount = max(count($otherAllowanceLabels), count($otherAllowanceAmounts), 1);

                                                for ($i = 0; $i < $otherAllowanceCount; $i++) {
                                                    $otherAllowanceRows[] = [
                                                        'label' => $otherAllowanceLabels[$i] ?? '',
                                                        'amount' => $otherAllowanceAmounts[$i] ?? '0.00',
                                                    ];
                                                }
                                            } else {
                                                foreach ($storedOtherAllowances as $row) {
                                                    $otherAllowanceRows[] = [
                                                        'label' => $row['label'] ?? '',
                                                        'amount' => number_format((float) ($row['amount'] ?? 0), 2, '.', ''),
                                                    ];
                                                }

                                                if (empty($otherAllowanceRows) && ((float) ($employee->other_allowance ?? 0)) > 0) {
                                                    $otherAllowanceRows[] = [
                                                        'label' => $employee->other_allowance_label ?: 'Other Allowance',
                                                        'amount' => number_format((float) $employee->other_allowance, 2, '.', ''),
                                                    ];
                                                }

                                                if (empty($otherAllowanceRows)) {
                                                    $otherAllowanceRows[] = [
                                                        'label' => '',
                                                        'amount' => '0.00',
                                                    ];
                                                }
                                            }
                                        @endphp

                                        <div id="otherAllowancesContainer" class="d-grid gap-3">
                                            @foreach($otherAllowanceRows as $index => $row)
                                                <div class="row g-3 align-items-end js-other-allowance-row">
                                                    <div class="col-md-7">
                                                        <label class="form-label">Other Allowance Label</label>
                                                        <input type="text" name="other_allowances[labels][]" class="form-control" placeholder="Enter custom label" value="{{ $row['label'] }}">
                                                        @error('other_allowances.labels.' . $index)<small class="text-danger">{{ $message }}</small>@enderror
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Amount</label>
                                                        <input type="number" step="0.01" min="0" name="other_allowances[amounts][]" class="form-control salary-component" placeholder="0.00" value="{{ $row['amount'] }}">
                                                        @error('other_allowances.amounts.' . $index)<small class="text-danger">{{ $message }}</small>@enderror
                                                    </div>
                                                    <div class="col-md-1 d-flex justify-content-end">
                                                        <button type="button" class="btn btn-outline-danger js-remove-other-allowance" title="Remove allowance">&times;</button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        <div class="mt-3">
                                            <button type="button" id="addOtherAllowanceRow" class="btn btn-sm btn-outline-primary">Add Other Allowance</button>
                                        </div>
                                    </div>

                                    <div class="alert alert-info mb-0 d-flex justify-content-between align-items-center">
                                        <span class="fw-semibold">Total Gross (Basic + Allowances + Other)</span>
                                        <span id="salaryComponentsTotal" class="fw-bold">0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Phone --}}
                    <div class="col-md-6">
                        <label for="phone" class="form-label fw-semibold">Phone</label>
                        <input type="text" class="form-control form-control-lg" id="phone" name="phone"
                               value="{{ old('phone', $employee->phone) }}" required>
                    </div>

                    {{-- Joining Date --}}
                    <div class="col-md-6">
                        <label for="joining_date" class="form-label fw-semibold">Joining Date</label>
                        <input type="date" class="form-control form-control-lg" id="joining_date" name="joining_date"
                               value="{{ old('joining_date', \Carbon\Carbon::parse($employee->joining_date)->format('Y-m-d')) }}" required>
                    </div>

                    {{-- Buttons --}}
                    <div class="col-12 mt-4 d-flex gap-3">
                        <button type="submit" class="btn btn-primary btn-lg px-5" style="background: linear-gradient(135deg, #1E90FF, #00BFFF); border:none;">
                            <i class="bi bi-save me-2"></i> Update Employee
                        </button>
                        <a href="{{ url('/employees') }}" class="btn btn-outline-secondary btn-lg px-5">
                            <i class="bi bi-x-circle me-2"></i> Cancel
                        </a>
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    const totalNode = document.getElementById('salaryComponentsTotal');
    const otherAllowancesContainer = document.getElementById('otherAllowancesContainer');
    const addOtherAllowanceRowButton = document.getElementById('addOtherAllowanceRow');

    const toNumber = (value) => {
        const normalized = String(value || '').replace(/,/g, '').trim();
        const parsed = parseFloat(normalized);
        return Number.isNaN(parsed) ? 0 : parsed;
    };

    const renderTotal = () => {
        let total = toNumber(document.getElementById('basic_salary').value) || 0;
        document.querySelectorAll('.salary-component').forEach((field) => {
            total += toNumber(field.value);
        });
        totalNode.textContent = total.toFixed(2);
    };

    document.addEventListener('input', function (event) {
        if (event.target.classList.contains('salary-component')) {
            renderTotal();
        }
    });

    const addOtherAllowanceRow = (label = '', amount = '0.00') => {
        const row = document.createElement('div');
        row.className = 'row g-3 align-items-end js-other-allowance-row';
        row.innerHTML = `
            <div class="col-md-7">
                <label class="form-label">Other Allowance Label</label>
                <input type="text" name="other_allowances[labels][]" class="form-control" placeholder="Enter custom label" value="${label}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Amount</label>
                <input type="number" step="0.01" min="0" name="other_allowances[amounts][]" class="form-control salary-component" placeholder="0.00" value="${amount}">
            </div>
            <div class="col-md-1 d-flex justify-content-end">
                <button type="button" class="btn btn-outline-danger js-remove-other-allowance" title="Remove allowance">&times;</button>
            </div>
        `;

        otherAllowancesContainer.appendChild(row);
        renderTotal();
    };

    addOtherAllowanceRowButton.addEventListener('click', function () {
        addOtherAllowanceRow();
    });

    document.addEventListener('click', function (event) {
        if (!event.target.classList.contains('js-remove-other-allowance')) {
            return;
        }

        const rows = otherAllowancesContainer.querySelectorAll('.js-other-allowance-row');
        if (rows.length <= 1) {
            const activeRow = rows[0];
            if (activeRow) {
                const labelInput = activeRow.querySelector('input[name="other_allowances[labels][]"]');
                const amountInput = activeRow.querySelector('input[name="other_allowances[amounts][]"]');
                if (labelInput) {
                    labelInput.value = '';
                }
                if (amountInput) {
                    amountInput.value = '0.00';
                }
            }
            renderTotal();
            return;
        }

        event.target.closest('.js-other-allowance-row')?.remove();
        renderTotal();
    });

    document.querySelectorAll('.js-money-format').forEach((field) => {
        field.addEventListener('blur', function () {
            if (this.value === '') {
                return;
            }

            this.value = toNumber(this.value).toFixed(2);
        });
    });

    renderTotal();

    document.getElementById('basic_salary').addEventListener('input', renderTotal);
});
</script>
@endpush
