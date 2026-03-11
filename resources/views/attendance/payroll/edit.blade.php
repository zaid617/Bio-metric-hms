@extends('layouts.app')

@section('title')
    Payroll Adjustment — {{ $payroll->employee->name ?? '#'.$payroll->id }}
@endsection

@push('css')
<style>
    .adj-type-card { cursor:pointer;border:2px solid transparent;transition:.15s; }
    .adj-type-card:hover,.adj-type-card.selected { border-color: currentColor; }
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
                <div class="row text-center">
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-muted small">Basic Salary</div>
                        <div class="fw-bold fs-5">PKR {{ number_format($payroll->basic_salary ?? $payroll->base_salary ?? 0,0) }}</div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-muted small">Awards / Incentives</div>
                        <div class="fw-bold fs-5 text-success">PKR {{ number_format($payroll->awards_total ?? $payroll->bonus ?? 0,0) }}</div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-muted small">Deductions</div>
                        <div class="fw-bold fs-5 text-danger">PKR {{ number_format($payroll->deductions_total ?? $payroll->deductions ?? 0,0) }}</div>
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

                    {{-- Type selector --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Adjustment Type <span class="text-danger">*</span></label>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <div class="adj-type-card card text-center text-success p-3" onclick="selectType('earning')">
                                    <span class="material-icons-outlined mb-1" style="font-size:28px">add_circle</span>
                                    <div class="fw-semibold">Earning / Incentive</div>
                                    <small class="text-muted">Additional salary, bonuses</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="adj-type-card card text-center text-warning p-3" onclick="selectType('award')">
                                    <span class="material-icons-outlined mb-1" style="font-size:28px">emoji_events</span>
                                    <div class="fw-semibold">Award</div>
                                    <small class="text-muted">Punctuality, performance</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="adj-type-card card text-center text-danger p-3" onclick="selectType('deduction')">
                                    <span class="material-icons-outlined mb-1" style="font-size:28px">remove_circle</span>
                                    <div class="fw-semibold">Deduction / Fine</div>
                                    <small class="text-muted">Fines, advances, absences</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Hidden type & code --}}
                    <input type="hidden" name="earning_notes" value="">
                    <input type="hidden" name="deduction_notes" value="">
                    <input type="hidden" name="award_notes" value="">

                    <div class="row g-3" id="adjustment_fields" style="display:none!important">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Code / Category <span class="text-danger">*</span></label>
                            <select id="code_select" class="form-select" onchange="updateCodeInput()">
                                <option value="">— Select type first —</option>
                            </select>
                            <input type="hidden" id="earning_code"   name="earning_code">
                            <input type="hidden" id="deduction_code" name="deduction_code">
                            <input type="hidden" id="award_code"     name="award_code">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Amount (PKR) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" id="amount_input" class="form-control" placeholder="0.00">
                            <input type="hidden" id="earning_amount"   name="earning_amount">
                            <input type="hidden" id="deduction_amount" name="deduction_amount">
                            <input type="hidden" id="award_amount"     name="award_amount">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Notes / Reason</label>
                            <textarea id="notes_input" class="form-control" rows="2"
                                      placeholder="Describe the reason for this adjustment..."></textarea>
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <a href="{{ route('attendance.payroll.show',$payroll->id) }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-warning px-4" id="submit_btn" disabled>
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
var currentType = null;
var codes = {
    earning: [
        {v:'ADDITIONAL_SALARY', l:'Additional Salary'},
        {v:'OVERTIME', l:'Overtime'},
        {v:'TREATMENT_EXTENSION_COMMISSION', l:'Treatment Extension Commission (10%)'},
        {v:'SATISFACTION_BONUS', l:'Patient Satisfaction Bonus'},
        {v:'ASSESSMENT_BONUS', l:'Staff Assessment Incentive (5%)'},
        {v:'REFERENCE_BONUS', l:'Patient Reference Reward'},
        {v:'PERSONAL_PATIENT_COMMISSION', l:'Personal Patient Commission (20%)'},
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

function selectType(type) {
    currentType = type;
    document.querySelectorAll('.adj-type-card').forEach(c => c.classList.remove('selected'));
    event.currentTarget.classList.add('selected');

    var sel = document.getElementById('code_select');
    sel.innerHTML = '<option value="">— Select code —</option>';
    (codes[type] || []).forEach(function(item){
        sel.innerHTML += '<option value="'+item.v+'">'+item.l+'</option>';
    });

    document.getElementById('adjustment_fields').style.cssText = '';
    document.getElementById('amount_input').value = '';
    document.getElementById('notes_input').value = '';
    updateSubmitBtn();
}

function updateCodeInput() {
    updateSubmitBtn();
}

function updateSubmitBtn() {
    var ready = currentType && document.getElementById('code_select').value && parseFloat(document.getElementById('amount_input').value || 0) > 0;
    document.getElementById('submit_btn').disabled = !ready;
}

document.getElementById('amount_input').addEventListener('input', updateSubmitBtn);

document.querySelector('form').addEventListener('submit', function(e) {
    var v = document.getElementById('code_select').value;
    var a = document.getElementById('amount_input').value;
    var n = document.getElementById('notes_input').value;
    if (currentType === 'earning') {
        document.getElementById('earning_code').value   = v;
        document.getElementById('earning_amount').value = a;
        document.getElementById('earning_notes').value  = n;
    } else if (currentType === 'award') {
        document.getElementById('award_code').value   = v;
        document.getElementById('award_amount').value = a;
        document.getElementById('award_notes').value  = n;
    } else if (currentType === 'deduction') {
        document.getElementById('deduction_code').value   = v;
        document.getElementById('deduction_amount').value = a;
        document.getElementById('deduction_notes').value  = n;
    }
});
</script>
@endpush
@endsection
