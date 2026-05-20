@extends('layouts.app')

@section('title', 'Employee Details')

@push('css')
<style>
    .salary-chip { display:inline-block; padding:2px 10px; border-radius:20px; font-size:.78rem; background:rgba(13,110,253,.1); color:#0d6efd; }
    .increment-badge-positive { color:#198754; font-weight:600; }
    .increment-badge-negative { color:#dc3545; font-weight:600; }
    .component-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(190px,1fr)); gap:.65rem; }
    .component-item { background:#f8f9fa; border-radius:6px; padding:.55rem .85rem; }
    .component-item .label { font-size:.7rem; text-transform:uppercase; letter-spacing:.04em; color:#6b7280; margin-bottom:2px; }
    .component-item .value { font-weight:600; font-size:.92rem; }

    /* Modal section dividers */
    .section-divider { display:flex; align-items:center; gap:.6rem; margin-bottom:.75rem; }
    .section-divider .section-badge { font-size:.7rem; font-weight:600; letter-spacing:.05em; padding:2px 10px; border-radius:20px; white-space:nowrap; }
    .section-divider hr { flex-grow:1; margin:0; border-color:#dee2e6; }

    /* Type toggle pills */
    .type-btn-group .btn-check:checked + .btn { background:#0d6efd; color:#fff; border-color:#0d6efd; }
    .type-btn-group .btn { min-width:130px; font-size:.88rem; }

    /* Gross summary card */
    .gross-summary { background:#f8f9fa; border:1px solid #e9ecef; border-radius:10px; padding:1rem 1.25rem; }
    .gross-summary .gs-row { display:flex; justify-content:space-between; align-items:center; padding:.3rem 0; }
    .gross-summary .gs-row:not(:last-child) { border-bottom:1px solid #e9ecef; }
    .gross-summary .gs-label { font-size:.8rem; color:#6b7280; }
    .gross-summary .gs-value { font-weight:600; font-size:.92rem; }

    /* Fixed form compact inputs */
    #fixedSection .form-label { font-size:.8rem; margin-bottom:.25rem; color:#374151; }
    #fixedSection .form-control { font-size:.875rem; }
</style>
@endpush

@section('content')
<x-page-title title="Employee" subtitle="Employee Details" />

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@php
    $latestIncrement = $salaryIncrements->first();

    $componentLabels = [
        'basic_salary'                        => 'Basic Salary',
        'incentive_sunday_roster'             => 'Sunday Roster Incentive',
        'incentive_home_visit'                => 'Home Visit Incentive',
        'incentive_speech_therapy'            => 'Speech Therapy Incentive',
        'incentive_dry_needling'              => 'Dry Needling Incentive',
        'allowance_allied_health_council'     => 'Allied Health Council',
        'allowance_house_job'                 => 'House Job Allowance',
        'allowance_conveyance'                => 'Conveyance Allowance',
        'allowance_medical'                   => 'Medical Allowance',
        'allowance_house_rent'                => 'House Rent Allowance',
        'allowance_branch_manager'            => 'Branch Manager Allowance',
        'allowance_assistant_branch_manager'  => 'Asst. Branch Manager Allowance',
        'other_allowance'                     => 'Other Allowance',
    ];

    $otherAllowances = is_string($employee->other_allowances)
        ? json_decode($employee->other_allowances, true)
        : $employee->other_allowances;
    $otherAllowances = is_array($otherAllowances) ? $otherAllowances : [];

    $currentGross = $employee->gross_salary;
@endphp

<div class="row g-4">

    {{-- ── Left: Basic Info ──────────────────────────────────────────────── --}}
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h4 class="mb-1">{{ $employee->prefix }} {{ $employee->name }}</h4>
                        <span class="salary-chip">{{ $employee->designation }}</span>
                    </div>
                    <a href="{{ url('/employees') }}" class="btn btn-outline-secondary btn-sm">Back</a>
                </div>

                <hr>

                <dl class="row g-2 mb-0" style="font-size:.9rem;">
                    <dt class="col-5 text-muted fw-normal">Branch</dt>
                    <dd class="col-7 mb-0">{{ $employee->branch->name ?? 'N/A' }}</dd>

                    <dt class="col-5 text-muted fw-normal">Department</dt>
                    <dd class="col-7 mb-0">{{ $employee->departmentRecord->name ?? $employee->department ?? 'N/A' }}</dd>

                    <dt class="col-5 text-muted fw-normal">Phone</dt>
                    <dd class="col-7 mb-0">{{ $employee->phone ?? 'N/A' }}</dd>

                    <dt class="col-5 text-muted fw-normal">Joining Date</dt>
                    <dd class="col-7 mb-0">
                        {{ $employee->joining_date ? \Carbon\Carbon::parse($employee->joining_date)->format('d M Y') : 'N/A' }}
                    </dd>

                    <dt class="col-5 text-muted fw-normal">Shift</dt>
                    <dd class="col-7 mb-0">{{ $employee->shift ?? 'N/A' }}</dd>

                    <dt class="col-5 text-muted fw-normal">Working Hours</dt>
                    <dd class="col-7 mb-0">{{ $employee->working_hours ?? 'N/A' }}h</dd>
                </dl>

                @if(!empty($employee->appointment_letter))
                    <hr>
                    <a href="{{ asset($employee->appointment_letter) }}" target="_blank" class="btn btn-outline-primary btn-sm w-100">
                        <span class="material-icons-outlined me-1" style="font-size:16px;vertical-align:middle">description</span>
                        View Appointment Letter
                    </a>
                    @php $ext = strtolower(pathinfo($employee->appointment_letter, PATHINFO_EXTENSION)); @endphp
                    @if(in_array($ext, ['jpg','jpeg','png','gif','webp']))
                        <img src="{{ asset($employee->appointment_letter) }}" class="img-fluid rounded mt-2 border" alt="Appointment Letter">
                    @endif
                @endif

                <hr>
                <a href="{{ url('/employees/'.$employee->id.'/edit') }}" class="btn btn-primary btn-sm w-100">
                    <span class="material-icons-outlined me-1" style="font-size:16px;vertical-align:middle">edit</span>
                    Edit Employee
                </a>
            </div>
        </div>
    </div>

    {{-- ── Right: Salary Section ──────────────────────────────────────────── --}}
    <div class="col-lg-8">

        {{-- Current Salary Components --}}
        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
                <h6 class="mb-0 fw-bold">
                    <span class="material-icons-outlined me-1 text-success" style="font-size:18px;vertical-align:middle">payments</span>
                    Current Salary Components
                </h6>
                <div class="d-flex gap-2 align-items-center">
                    <span class="badge bg-success fs-6">Gross: PKR {{ number_format($currentGross, 2) }}</span>
                    @if($canManageSalary)
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addIncrementModal">
                            <span class="material-icons-outlined me-1" style="font-size:15px;vertical-align:middle">add</span>
                            Add Increment
                        </button>
                    @endif
                </div>
            </div>
            <div class="card-body p-3">
                <div class="component-grid">
                    @foreach($componentLabels as $field => $label)
                        @php $val = (float) ($employee->$field ?? 0); @endphp
                        @if($val > 0)
                            <div class="component-item">
                                <div class="label">{{ $label }}</div>
                                <div class="value">{{ number_format($val, 2) }}</div>
                            </div>
                        @endif
                    @endforeach
                    @foreach($otherAllowances as $oa)
                        @if((float)($oa['amount'] ?? 0) > 0)
                            <div class="component-item">
                                <div class="label">{{ $oa['label'] ?? 'Other Allowance' }}</div>
                                <div class="value">{{ number_format((float)$oa['amount'], 2) }}</div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Latest Increment Card --}}
        @if($latestIncrement)
        <div class="card shadow-sm border-0 rounded-4 mb-4" style="border-left:4px solid #0d6efd !important;">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold">
                    <span class="material-icons-outlined me-1 text-primary" style="font-size:18px;vertical-align:middle">trending_up</span>
                    Latest Increment
                    <span class="badge bg-primary ms-2">{{ $latestIncrement->effective_from->format('d M Y') }}</span>
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3 text-center">
                        <div class="text-muted" style="font-size:.78rem">Previous Gross</div>
                        <div class="fw-bold">PKR {{ number_format($latestIncrement->previous_gross, 2) }}</div>
                    </div>
                    <div class="col-md-1 text-center d-flex align-items-center justify-content-center">
                        <span class="material-icons-outlined text-muted">arrow_forward</span>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="text-muted" style="font-size:.78rem">New Gross</div>
                        <div class="fw-bold text-success">PKR {{ number_format($latestIncrement->new_gross, 2) }}</div>
                    </div>
                    <div class="col-md-2 text-center">
                        <div class="text-muted" style="font-size:.78rem">Net Change</div>
                        <div class="fw-bold {{ $latestIncrement->increment_amount >= 0 ? 'increment-badge-positive' : 'increment-badge-negative' }}">
                            {{ $latestIncrement->increment_amount >= 0 ? '+' : '' }}{{ number_format($latestIncrement->increment_amount, 2) }}
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="text-muted" style="font-size:.78rem">Type</div>
                        <span class="badge bg-info text-dark">{{ ucfirst($latestIncrement->increment_type) }}</span>
                    </div>
                </div>
                @if($latestIncrement->reason)
                    <div class="mt-2 text-muted" style="font-size:.85rem">
                        <span class="material-icons-outlined me-1" style="font-size:15px;vertical-align:middle">comment</span>
                        {{ $latestIncrement->reason }}
                    </div>
                @endif
                <div class="mt-1" style="font-size:.78rem;color:#9ca3af;">
                    Added by {{ $latestIncrement->creator->name ?? 'System' }} on {{ $latestIncrement->created_at->format('d M Y H:i') }}
                </div>
            </div>
        </div>
        @endif

        {{-- Increment History Table --}}
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold">
                    <span class="material-icons-outlined me-1 text-secondary" style="font-size:18px;vertical-align:middle">history</span>
                    Increment History
                </h6>
            </div>
            <div class="card-body p-0">
                @if($salaryIncrements->isEmpty())
                    <div class="text-center text-muted py-5">
                        <span class="material-icons-outlined d-block mb-2" style="font-size:40px">inbox</span>
                        No salary increments recorded yet.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle" style="font-size:.875rem;">
                            <thead class="table-light">
                                <tr>
                                    <th>Effective From</th>
                                    <th>Previous Gross</th>
                                    <th>New Gross</th>
                                    <th>Net Change</th>
                                    <th>Type</th>
                                    <th>Reason</th>
                                    <th>Added By</th>
                                    @if($canManageSalary)<th class="text-end">Actions</th>@endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($salaryIncrements as $increment)
                                    <tr>
                                        <td><strong>{{ $increment->effective_from->format('d M Y') }}</strong></td>
                                        <td>{{ number_format($increment->previous_gross, 2) }}</td>
                                        <td class="text-success fw-semibold">{{ number_format($increment->new_gross, 2) }}</td>
                                        <td class="{{ $increment->increment_amount >= 0 ? 'increment-badge-positive' : 'increment-badge-negative' }}">
                                            {{ $increment->increment_amount >= 0 ? '+' : '' }}{{ number_format($increment->increment_amount, 2) }}
                                        </td>
                                        <td><span class="badge bg-info text-dark">{{ ucfirst($increment->increment_type) }}</span></td>
                                        <td class="text-muted">{{ $increment->reason ?? '—' }}</td>
                                        <td class="text-muted" style="font-size:.8rem;">
                                            {{ $increment->creator->name ?? 'System' }}<br>
                                            <span style="color:#9ca3af;">{{ $increment->created_at->format('d M Y') }}</span>
                                        </td>
                                        @if($canManageSalary)
                                        <td class="text-end">
                                            <button class="btn btn-outline-warning btn-sm me-1"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editIncrementModal"
                                                    data-increment-id="{{ $increment->id }}"
                                                    data-increment-reason="{{ $increment->reason }}"
                                                    onclick="prefillEditModal(this)"
                                                    title="Edit reason">
                                                <span class="material-icons-outlined" style="font-size:15px;">edit</span>
                                            </button>
                                            <form method="POST"
                                                  action="{{ route('employees.salary-increments.destroy', [$employee->id, $increment->id]) }}"
                                                  class="d-inline"
                                                  onsubmit="return confirm('Delete this increment? The employee salary will be recalculated.')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-outline-danger btn-sm" type="submit" title="Delete">
                                                    <span class="material-icons-outlined" style="font-size:15px;">delete</span>
                                                </button>
                                            </form>
                                        </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-3 py-2">{{ $salaryIncrements->links() }}</div>
                @endif
            </div>
        </div>

    </div>
</div>


@if($canManageSalary)
{{-- ══════════ ADD INCREMENT MODAL ══════════ --}}
<div class="modal fade" id="addIncrementModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">

            <form method="POST" action="{{ route('employees.salary-increments.store', $employee->id) }}" id="addIncrementForm">
                @csrf
                <input type="hidden" name="_modal" value="add">

                {{-- Sticky Header --}}
                <div class="modal-header bg-primary text-white border-0 flex-shrink-0">
                    <div>
                        <h5 class="modal-title mb-0">Add Salary Increment</h5>
                        <small class="opacity-75">{{ $employee->prefix }} {{ $employee->name }}</small>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                {{-- Scrollable Body --}}
                <div class="modal-body px-4 py-3" style="overflow-y:auto;">

                    {{-- Validation errors --}}
                    @if($errors->any() && old('_modal') === 'add')
                        <div class="alert alert-danger py-2 mb-3">
                            <ul class="mb-0 ps-3">
                                @foreach($errors->all() as $error)
                                    <li style="font-size:.875rem">{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- ── Type selector ── --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold mb-2">Increment Type <span class="text-danger">*</span></label>
                        <div class="btn-group type-btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="increment_type" id="type_pct"
                                   value="percentage" autocomplete="off"
                                   {{ old('increment_type') === 'percentage' ? 'checked' : '' }}
                                   onchange="toggleIncrementType('percentage')">
                            <label class="btn btn-outline-primary" for="type_pct">
                                <span class="material-icons-outlined me-1" style="font-size:16px;vertical-align:middle">percent</span>
                                Percentage Raise
                            </label>
                            <input type="radio" class="btn-check" name="increment_type" id="type_fixed"
                                   value="fixed" autocomplete="off"
                                   {{ old('increment_type') === 'fixed' ? 'checked' : '' }}
                                   onchange="toggleIncrementType('fixed')">
                            <label class="btn btn-outline-primary" for="type_fixed">
                                <span class="material-icons-outlined me-1" style="font-size:16px;vertical-align:middle">tune</span>
                                Fixed New Values
                            </label>
                        </div>
                    </div>

                    {{-- ── Date + Reason ── --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Effective From <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="effective_from"
                                   value="{{ old('effective_from', date('Y-m-d')) }}"
                                   max="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">Reason / Note</label>
                            <input type="text" class="form-control" name="reason"
                                   value="{{ old('reason') }}"
                                   placeholder="e.g. Annual review, performance bonus…">
                        </div>
                    </div>

                    {{-- ── PERCENTAGE section ── --}}
                    <div id="percentageSection" style="display:none;">
                        <hr class="my-3">
                        <div class="row g-3 align-items-start">
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Percentage Increase <span class="text-danger">*</span></label>
                                <div class="input-group input-group-lg">
                                    <input type="number" step="0.01" min="0.01" max="1000"
                                           class="form-control" name="percentage" id="percentageInput"
                                           value="{{ old('percentage') }}" placeholder="e.g. 10"
                                           oninput="updatePercentagePreview()">
                                    <span class="input-group-text fw-bold">%</span>
                                </div>
                                <div class="form-text">Applied equally to all salary components</div>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label fw-semibold">Estimated Result</label>
                                <div class="gross-summary">
                                    <div class="gs-row">
                                        <span class="gs-label">Current Gross</span>
                                        <span class="gs-value">PKR {{ number_format($currentGross, 2) }}</span>
                                    </div>
                                    <div class="gs-row">
                                        <span class="gs-label">New Gross</span>
                                        <span class="gs-value text-success" id="pctNewGross">—</span>
                                    </div>
                                    <div class="gs-row">
                                        <span class="gs-label">Net Increase</span>
                                        <span class="gs-value text-primary" id="pctNetIncrease">—</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <a class="text-muted small text-decoration-none" data-bs-toggle="collapse" href="#pctDetailTable" role="button">
                                <span class="material-icons-outlined" style="font-size:14px;vertical-align:middle">expand_more</span>
                                Show component breakdown
                            </a>
                            <div class="collapse mt-2" id="pctDetailTable">
                                <div class="table-responsive rounded border">
                                    <table class="table table-sm mb-0" style="font-size:.82rem;">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Component</th>
                                                <th class="text-end">Current (PKR)</th>
                                                <th class="text-end text-success">After (PKR)</th>
                                            </tr>
                                        </thead>
                                        <tbody id="percentagePreviewBody">
                                            @foreach($componentLabels as $field => $label)
                                                @php $val = (float) ($employee->$field ?? 0); @endphp
                                                <tr data-field="{{ $field }}" data-current="{{ $val }}">
                                                    <td class="text-muted">{{ $label }}</td>
                                                    <td class="text-end">{{ number_format($val, 2) }}</td>
                                                    <td class="text-end preview-new fw-semibold text-success">—</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ── FIXED section — compact table layout ── --}}
                    <div id="fixedSection" style="display:none;">
                        <hr class="my-3">
                        <p class="text-muted mb-2" style="font-size:.83rem;">
                            Enter the <strong>new absolute value</strong> for each component. Pre-filled with current values — only edit what changed.
                        </p>

                        <div class="table-responsive rounded border">
                            <table class="table table-sm table-hover align-middle mb-0" style="font-size:.85rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:45%">Component</th>
                                        <th class="text-end" style="width:25%">Current (PKR)</th>
                                        <th style="width:30%">New Value (PKR)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- Base Pay --}}
                                    <tr class="table-primary">
                                        <td colspan="3" class="fw-semibold py-1" style="font-size:.75rem;letter-spacing:.05em;text-transform:uppercase;">Base Pay</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Basic Salary</td>
                                        <td class="text-end fw-semibold">{{ number_format((float)($employee->basic_salary ?? 0), 2) }}</td>
                                        <td>
                                            <input type="number" step="0.01" min="0" class="form-control form-control-sm fixed-input"
                                                   name="basic_salary"
                                                   value="{{ old('basic_salary', $employee->basic_salary ?? 0) }}">
                                        </td>
                                    </tr>

                                    {{-- Allowances --}}
                                    <tr class="table-success">
                                        <td colspan="3" class="fw-semibold py-1" style="font-size:.75rem;letter-spacing:.05em;text-transform:uppercase;">Allowances</td>
                                    </tr>
                                    @foreach([
                                        'allowance_allied_health_council'    => 'Allied Health Council',
                                        'allowance_house_job'                => 'House Job',
                                        'allowance_conveyance'               => 'Conveyance',
                                        'allowance_medical'                  => 'Medical',
                                        'allowance_house_rent'               => 'House Rent',
                                        'allowance_branch_manager'           => 'Branch Manager',
                                        'allowance_assistant_branch_manager' => 'Asst. Branch Manager',
                                        'other_allowance'                    => 'Other Allowance',
                                    ] as $field => $label)
                                    <tr>
                                        <td class="text-muted">{{ $label }}</td>
                                        <td class="text-end fw-semibold">{{ number_format((float)($employee->$field ?? 0), 2) }}</td>
                                        <td>
                                            <input type="number" step="0.01" min="0" class="form-control form-control-sm fixed-input"
                                                   name="{{ $field }}"
                                                   value="{{ old($field, $employee->$field ?? 0) }}">
                                        </td>
                                    </tr>
                                    @endforeach

                                    {{-- Incentives --}}
                                    <tr class="table-warning">
                                        <td colspan="3" class="fw-semibold py-1" style="font-size:.75rem;letter-spacing:.05em;text-transform:uppercase;">Incentives</td>
                                    </tr>
                                    @foreach([
                                        'incentive_sunday_roster'   => 'Sunday Roster',
                                        'incentive_home_visit'      => 'Home Visit',
                                        'incentive_speech_therapy'  => 'Speech Therapy',
                                        'incentive_dry_needling'    => 'Dry Needling',
                                    ] as $field => $label)
                                    <tr>
                                        <td class="text-muted">{{ $label }}</td>
                                        <td class="text-end fw-semibold">{{ number_format((float)($employee->$field ?? 0), 2) }}</td>
                                        <td>
                                            <input type="number" step="0.01" min="0" class="form-control form-control-sm fixed-input"
                                                   name="{{ $field }}"
                                                   value="{{ old($field, $employee->$field ?? 0) }}">
                                        </td>
                                    </tr>
                                    @endforeach

                                    {{-- Gross total row --}}
                                    <tr class="table-secondary fw-bold">
                                        <td>New Gross Total</td>
                                        <td class="text-end">{{ number_format($currentGross, 2) }}</td>
                                        <td class="text-success" id="fixedNewGross">{{ number_format($currentGross, 2) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        @if(!empty($otherAllowances))
                        <div class="alert alert-info py-2 mt-2 mb-0" style="font-size:.82rem;">
                            <strong>Note:</strong> {{ count($otherAllowances) }} structured other allowance(s) carry over unchanged.
                            Edit them via the <a href="{{ url('/employees/'.$employee->id.'/edit') }}">Employee Edit</a> form.
                        </div>
                        @endif
                    </div>

                </div>

                {{-- Sticky Footer --}}
                <div class="modal-footer border-top bg-light flex-shrink-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <span class="material-icons-outlined me-1" style="font-size:16px;vertical-align:middle">save</span>
                        Save Increment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


{{-- ══════════ EDIT INCREMENT MODAL ══════════ --}}
<div class="modal fade" id="editIncrementModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" id="editIncrementForm" action="">
                @csrf
                @method('PUT')

                <div class="modal-header bg-warning text-dark border-0">
                    <h5 class="modal-title mb-0">Edit Increment Note</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body px-4 py-3">
                    <p class="text-muted mb-3" style="font-size:.85rem;">
                        Only the reason/note can be edited. Salary values are locked to preserve the audit trail.
                    </p>
                    <label class="form-label fw-semibold">Reason / Note</label>
                    <textarea class="form-control" name="reason" id="editReasonField"
                              rows="3" maxlength="1000"
                              placeholder="Enter reason for this increment…"></textarea>
                </div>

                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning px-4">Update Note</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endif {{-- canManageSalary --}}


@push('script')
<script>
function toggleIncrementType(type) {
    var isPct   = type === 'percentage';
    var isFixed = type === 'fixed';

    document.getElementById('percentageSection').style.display = isPct   ? '' : 'none';
    document.getElementById('fixedSection').style.display      = isFixed ? '' : 'none';

    var pctInput = document.getElementById('percentageInput');
    if (pctInput) pctInput.required = isPct;

    document.querySelectorAll('.fixed-input').forEach(function(el) {
        el.required = isFixed;
    });

    if (isPct)   updatePercentagePreview();
    if (isFixed) updateFixedGross();
}

function updatePercentagePreview() {
    var pct        = parseFloat(document.getElementById('percentageInput').value) || 0;
    var multiplier = 1 + pct / 100;
    var totalNew   = 0;

    document.querySelectorAll('#percentagePreviewBody tr[data-current]').forEach(function(row) {
        var current = parseFloat(row.getAttribute('data-current')) || 0;
        var newVal  = Math.round(current * multiplier * 100) / 100;
        totalNew   += newVal;
        row.querySelector('.preview-new').textContent =
            newVal.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
    });

    var currentGross = {{ $currentGross }};
    var netIncrease  = Math.round((totalNew - currentGross) * 100) / 100;

    var fmt = function(n) { return 'PKR ' + n.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}); };
    document.getElementById('pctNewGross').textContent    = pct > 0 ? fmt(totalNew)    : '—';
    document.getElementById('pctNetIncrease').textContent = pct > 0 ? fmt(netIncrease) : '—';
}

function updateFixedGross() {
    var total = 0;
    document.querySelectorAll('.fixed-input').forEach(function(el) {
        total += parseFloat(el.value) || 0;
    });
    document.getElementById('fixedNewGross').textContent =
        'PKR ' + total.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.fixed-input').forEach(function(el) {
        el.addEventListener('input', updateFixedGross);
    });

    @if($errors->any() && old('_modal') === 'add')
        var modal = new bootstrap.Modal(document.getElementById('addIncrementModal'));
        modal.show();
        var savedType = '{{ old('increment_type') }}';
        if (savedType) {
            var radio = document.getElementById(savedType === 'percentage' ? 'type_pct' : 'type_fixed');
            if (radio) { radio.checked = true; toggleIncrementType(savedType); }
        }
    @endif
});

function prefillEditModal(btn) {
    var id      = btn.getAttribute('data-increment-id');
    var reason  = btn.getAttribute('data-increment-reason') || '';
    var baseUrl = '{{ url('/employees/'.$employee->id.'/salary-increments') }}/';

    document.getElementById('editIncrementForm').action = baseUrl + id;
    document.getElementById('editReasonField').value    = reason;
}
</script>
@endpush
@endsection
