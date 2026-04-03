@extends('layouts.app')

@section('title')
    Payroll Adjustment — {{ $payroll->employee->name ?? '#'.$payroll->id }}
@endsection

@push('css')
<style>
    .table td,
    .table th {
        vertical-align: middle;
    }
</style>
@endpush

@section('content')
<x-page-title title="Add Adjustment to Payroll"
    subtitle="{{ $payroll->employee->name ?? '' }} — {{ \Carbon\Carbon::create($payroll->year,$payroll->month)->format('F Y') }}" />

<div class="row">
    <div class="col-xl-10 mx-auto">

        {{-- Current Summary --}}
        <div class="card mb-3">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Current Payroll Summary</h6>
                <a href="{{ route('attendance.payroll.show',$payroll->id) }}" class="btn btn-sm btn-light">
                    View Full Details
                </a>
            </div>
            <div class="card-body">
                @php
                    $incentivesTotal = max(
                        0,
                        (float) ($payroll->calculated_salary ?? 0) - (float) ($payroll->basic_salary ?? $payroll->base_salary ?? 0)
                    );
                    $awardsTotal = (float) ($payroll->awards_total ?? $payroll->bonus ?? 0);
                    $deductionsTotal = (float) ($payroll->deductions_total ?? $payroll->deductions ?? 0);
                @endphp
                <div class="row text-center">
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-muted small">Basic Salary</div>
                        <div class="fw-bold fs-5">PKR {{ number_format($payroll->basic_salary ?? $payroll->base_salary ?? 0,0) }}</div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-muted small">Incentives + Awards</div>
                        <div class="fw-bold fs-5 text-success">PKR {{ number_format($incentivesTotal + $awardsTotal,0) }}</div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-muted small">Deductions</div>
                        <div class="fw-bold fs-5 text-danger">PKR {{ number_format($deductionsTotal,0) }}</div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-muted small">Current Net Salary</div>
                        <div class="fw-bold fs-5 text-success">PKR {{ number_format($payroll->final_salary ?? $payroll->final_settlement ?? 0,0) }}</div>
                    </div>
                </div>
            </div>
        </div>

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

        {{-- Adjustment Form --}}
        <div class="card mb-3">
            <div class="card-header bg-warning bg-opacity-25">
                <h6 class="mb-0"><span class="material-icons-outlined me-2" style="vertical-align:middle">tune</span>Add New Adjustment</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('attendance.payroll.update',$payroll->id) }}" method="POST">
                    @csrf @method('PUT')

                    @if($errors->any())
                        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                    @endif

                    <div class="alert alert-info py-2">
                        Add one or more rows in each section below. All incentives, awards, and deductions will be saved together and payroll will recalculate once.
                    </div>

                    <div class="card mb-3 border-success border-opacity-25">
                        <div class="card-header bg-success bg-opacity-10 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 text-success">Incentives / Earnings</h6>
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="addRow('earning')">
                                <span class="material-icons-outlined" style="font-size:16px;vertical-align:middle">add</span>
                                Add Row
                            </button>
                        </div>
                        <div class="px-3 pt-2 small text-muted">Overtime is calculated automatically from attendance and cannot be added manually here.</div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width:32%">Code</th>
                                            <th style="width:20%">Amount (PKR)</th>
                                            <th>Notes</th>
                                            <th style="width:70px"></th>
                                        </tr>
                                    </thead>
                                    <tbody data-rows="earning"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3 border-warning border-opacity-25">
                        <div class="card-header bg-warning bg-opacity-10 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 text-warning">Awards</h6>
                            <button type="button" class="btn btn-sm btn-outline-warning" onclick="addRow('award')">
                                <span class="material-icons-outlined" style="font-size:16px;vertical-align:middle">add</span>
                                Add Row
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width:32%">Code</th>
                                            <th style="width:20%">Amount (PKR)</th>
                                            <th>Notes</th>
                                            <th style="width:70px"></th>
                                        </tr>
                                    </thead>
                                    <tbody data-rows="award"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3 border-danger border-opacity-25">
                        <div class="card-header bg-danger bg-opacity-10 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 text-danger">Deductions</h6>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="addRow('deduction')">
                                <span class="material-icons-outlined" style="font-size:16px;vertical-align:middle">add</span>
                                Add Row
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width:32%">Code</th>
                                            <th style="width:20%">Amount (PKR)</th>
                                            <th>Notes</th>
                                            <th style="width:70px"></th>
                                        </tr>
                                    </thead>
                                    <tbody data-rows="deduction"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <a href="{{ route('attendance.payroll.show',$payroll->id) }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-warning px-4">
                            <span class="material-icons-outlined me-1" style="vertical-align:middle;font-size:18px">save</span>
                            Save & Recalculate
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Existing adjustments table --}}
        @if($payroll->adjustments && $payroll->adjustments->count()>0)
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Existing Adjustments ({{ $payroll->adjustments->count() }})</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr><th>Type</th><th>Code/Title</th><th>Amount</th><th>Notes</th><th>By</th></tr>
                    </thead>
                    <tbody>
                        @foreach($payroll->adjustments as $adj)
                        <tr>
                            <td><span class="badge @if($adj->adjustment_type=='deduction') bg-danger @elseif($adj->adjustment_type=='award') bg-warning text-dark @else bg-success @endif">{{ ucfirst($adj->adjustment_type) }}</span></td>
                            <td>{{ $adj->title ?: str_replace('_',' ',$adj->code) }}</td>
                            <td class="{{ $adj->adjustment_type=='deduction'?'text-danger':'text-success' }} fw-semibold">
                                {{ $adj->adjustment_type=='deduction'?'-':'+' }} PKR {{ number_format($adj->amount,0) }}
                            </td>
                            <td class="small text-muted">{{ $adj->notes ?? $adj->reason ?? '—' }}</td>
                            <td class="small">{{ $adj->creator->name ?? 'System' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    </div>
</div>

@push('scripts')
<script>
var codes = {
    earning: [
        {v:'ADDITIONAL_SALARY', l:'Additional Salary'},
        {v:'TREATMENT_EXTENSION_COMMISSION', l:'Treatment Extension Commission (10%)'},
        {v:'SATISFACTION_BONUS', l:'Patient Satisfaction Bonus'},
        {v:'ASSESSMENT_BONUS', l:'Staff Assessment Incentive (5%)'},
        {v:'REFERENCE_BONUS', l:'Patient Reference Reward'},
        {v:'PERSONAL_PATIENT_COMMISSION', l:'Personal Patient Commission (20%)'},
        {v:'SUNDAY_ROSTER', l:'Sunday Roster and home Visit'},
        {v:'SPEECH_THERAPY_INCENTIVES', l:'Speech therapy'},
        {v:'CUSTOM', l:'Custom Earning'},
    ],
    award: [
        {v:'PUNCTUALITY_AWARD', l:'Punctuality Award'},
        {v:'CUSTOM', l:'Custom Award'},
    ],
    deduction: [
        {v:'SESSION_NUMBER_MISSING', l:'Missing Session Number'},
        {v:'WRONG_EMR_NUMBER', l:'Wrong EMR Number'},
        {v:'TIME_MISSING', l:'Missing Time Entry'},
        {v:'WRONG_PATIENT_NAME', l:'Wrong Patient Name'},
        {v:'ABSENT', l:'Absence Deduction'},
        {v:'LATE_COMING', l:'Late Coming'},
        {v:'ADVANCE_SALARY_DEDUCTION', l:'Advance Salary Deduction'},
        {v:'NO_SCRUB', l:'Not Wearing Scrub'},
        {v:'NO_ID_CARD', l:'Not Wearing ID Card'},
        {v:'LATE_UPDATE', l:'Late Record Update'},
        {v:'CUSTOM', l:'Custom Deduction'},
    ]
};

var sectionKey = {
    earning: 'earnings',
    award: 'awards',
    deduction: 'deductions_items'
};

var rowIndex = {
    earning: 0,
    award: 0,
    deduction: 0
};

function escapeAttr(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

function getCodeOptions(type, selectedCode) {
    var options = '<option value="">Select code</option>';
    (codes[type] || []).forEach(function(item) {
        var selected = selectedCode === item.v ? ' selected' : '';
        options += '<option value="' + item.v + '"' + selected + '>' + item.l + '</option>';
    });

    return options;
}

function buildRow(type, index, rowData) {
    var payloadKey = sectionKey[type];
    var code = String(rowData.code || '');
    var amount = rowData.amount === 0 ? '0' : String(rowData.amount || '');
    var notes = escapeAttr(rowData.notes || '');

    return '' +
        '<tr>' +
            '<td><select class="form-select form-select-sm" name="' + payloadKey + '[' + index + '][code]">' + getCodeOptions(type, code) + '</select></td>' +
            '<td><input type="number" step="0.01" min="0" class="form-control form-control-sm" name="' + payloadKey + '[' + index + '][amount]" value="' + escapeAttr(amount) + '" placeholder="0.00"></td>' +
            '<td><input type="text" class="form-control form-control-sm" name="' + payloadKey + '[' + index + '][notes]" value="' + notes + '" placeholder="Optional notes"></td>' +
            '<td class="text-end"><button type="button" class="btn btn-sm btn-outline-secondary" onclick="removeRow(this)"><span class="material-icons-outlined" style="font-size:16px;vertical-align:middle">delete</span></button></td>' +
        '</tr>';
}

function addRow(type, rowData) {
    var tbody = document.querySelector('[data-rows="' + type + '"]');
    if (!tbody) {
        return;
    }

    var index = rowIndex[type]++;
    tbody.insertAdjacentHTML('beforeend', buildRow(type, index, rowData || {}));
}

function removeRow(button) {
    var row = button.closest('tr');
    if (row) {
        row.remove();
    }
}

function normalizeRows(value) {
    if (Array.isArray(value)) {
        return value;
    }

    if (value && typeof value === 'object') {
        return Object.values(value);
    }

    return [];
}

document.addEventListener('DOMContentLoaded', function() {
    var oldEarnings = normalizeRows(@json(old('earnings', [])));
    var oldAwards = normalizeRows(@json(old('awards', [])));
    var oldDeductions = normalizeRows(@json(old('deductions_items', [])));

    if (oldEarnings.length > 0) {
        oldEarnings.forEach(function(row) { addRow('earning', row || {}); });
    } else {
        addRow('earning');
    }

    if (oldAwards.length > 0) {
        oldAwards.forEach(function(row) { addRow('award', row || {}); });
    } else {
        addRow('award');
    }

    if (oldDeductions.length > 0) {
        oldDeductions.forEach(function(row) { addRow('deduction', row || {}); });
    } else {
        addRow('deduction');
    }
});
</script>
@endpush
@endsection
