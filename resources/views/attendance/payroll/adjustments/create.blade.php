@extends('layouts.app')

@section('title') Add Payroll Adjustment @endsection

@push('css')
<style>
    .type-card { cursor:pointer; border:2px solid transparent; transition:.15s; }
    .type-card:hover { transform:translateY(-2px); box-shadow:0 4px 12px rgba(0,0,0,.1); }
    .type-card.selected { box-shadow:0 4px 12px rgba(0,0,0,.12); }
    .type-card.selected.earning   { border-color:#198754!important; background:#f0faf4; }
    .type-card.selected.award     { border-color:#ffc107!important; background:#fffdf0; }
    .type-card.selected.deduction { border-color:#dc3545!important; background:#fff5f5; }
    #fields-section { display:none; }
</style>
@endpush

@section('content')
<x-page-title title="Add Payroll Adjustment" subtitle="Create a standalone adjustment for an employee" />

<div class="row">
    <div class="col-xl-8 mx-auto">

        @if($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        <form action="{{ route('attendance.payroll.adjustments.store') }}" method="POST">
            @csrf

            {{-- Employee + Period --}}
            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0">Employee & Period</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Employee <span class="text-danger">*</span></label>
                            <select name="employee_id" class="form-select" required>
                                <option value="">— Select Employee —</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}"
                                        {{ old('employee_id', request('employee_id'))==$emp->id ? 'selected' : '' }}>
                                        {{ $emp->name }} @if($emp->employee_id)({{ $emp->employee_id }})@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Month <span class="text-danger">*</span></label>
                            <select name="month" class="form-select" required>
                                @for($m=1;$m<=12;$m++)
                                    <option value="{{ $m }}" {{ old('month', request('month', date('n')))==$m ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::create(null,$m)->format('F') }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Year <span class="text-danger">*</span></label>
                            <input type="number" name="year" class="form-control"
                                   value="{{ old('year', request('year', date('Y'))) }}"
                                   min="2020" max="2100" required>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Type selector --}}
            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0">Adjustment Type <span class="text-danger">*</span></h6></div>
                <div class="card-body">
                    <input type="hidden" name="adjustment_type" id="adj_type" value="{{ old('adjustment_type') }}">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="type-card earning card text-center p-3 {{ old('adjustment_type')=='earning'?'selected':'' }}"
                                 onclick="selectType('earning',this)">
                                <span class="material-icons-outlined text-success mb-2" style="font-size:32px">add_circle_outline</span>
                                <div class="fw-bold text-success">Earning</div>
                                <div class="text-muted small mt-1">Commission, bonus, overtime</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="type-card award card text-center p-3 {{ old('adjustment_type')=='award'?'selected':'' }}"
                                 onclick="selectType('award',this)">
                                <span class="material-icons-outlined text-warning mb-2" style="font-size:32px">emoji_events</span>
                                <div class="fw-bold text-warning">Award</div>
                                <div class="text-muted small mt-1">Punctuality, performance</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="type-card deduction card text-center p-3 {{ old('adjustment_type')=='deduction'?'selected':'' }}"
                                 onclick="selectType('deduction',this)">
                                <span class="material-icons-outlined text-danger mb-2" style="font-size:32px">remove_circle_outline</span>
                                <div class="fw-bold text-danger">Deduction</div>
                                <div class="text-muted small mt-1">Fine, penalty, advance</div>
                            </div>
                        </div>
                    </div>
                    @error('adjustment_type')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                </div>
            </div>

            {{-- Details (shown after type selected) --}}
            <div class="card mb-3" id="fields-section">
                <div class="card-header"><h6 class="mb-0">Adjustment Details</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Code / Category <span class="text-danger">*</span></label>
                            <select name="code" id="code_sel" class="form-select" required>
                                <option value="">— Select type first —</option>
                            </select>
                            @error('code')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Amount (PKR) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">PKR</span>
                                <input type="number" name="amount" step="0.01" min="0.01"
                                       class="form-control" placeholder="0.00"
                                       value="{{ old('amount') }}" required>
                            </div>
                            @error('amount')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Title / Label <span class="text-muted small">(optional)</span></label>
                            <input type="text" name="title" class="form-control"
                                   placeholder="Short descriptive label"
                                   value="{{ old('title') }}" maxlength="191">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Notes / Reason</label>
                            <textarea name="notes" class="form-control" rows="3"
                                      placeholder="Describe why this adjustment is being made...">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-end">
                <a href="{{ route('attendance.payroll.adjustments.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary px-4" id="submit_btn">
                    <span class="material-icons-outlined me-1" style="vertical-align:middle;font-size:18px">save</span> Save Adjustment
                </button>
            </div>
        </form>

    </div>
</div>
@endsection

@push('scripts')
<script>
var allCodes = {
    earning: [
        {v:'ADDITIONAL_SALARY',         l:'Additional Salary'},
        {v:'TREATMENT_EXTENSION_COMMISSION', l:'Treatment Extension Commission (10%)'},
        {v:'SATISFACTION_BONUS',         l:'Patient Satisfaction Bonus'},
        {v:'ASSESSMENT_BONUS',           l:'Staff Assessment Incentive (5%)'},
        {v:'REFERENCE_BONUS',            l:'Patient Reference Reward'},
        {v:'PERSONAL_PATIENT_COMMISSION',l:'Personal Patient Commission (20%)'},
        {v:'CUSTOM',                     l:'Custom Earning'},
    ],
    award: [
        {v:'PUNCTUALITY_AWARD', l:'Punctuality Award (PKR 2,000)'},
        {v:'CUSTOM',            l:'Custom Award'},
    ],
    deduction: [
        {v:'SESSION_NUMBER_MISSING',  l:'Missing Session Number'},
        {v:'WRONG_EMR_NUMBER',        l:'Wrong EMR Number'},
        {v:'TIME_MISSING',            l:'Missing Time Entry'},
        {v:'WRONG_PATIENT_NAME',      l:'Wrong Patient Name'},
        {v:'ABSENT',                  l:'Absence Deduction'},
        {v:'LATE_COMING',             l:'Late Coming Fine'},
        {v:'ADVANCE_SALARY_DEDUCTION',l:'Advance Salary Deduction'},
        {v:'NO_SCRUB',                l:'Not Wearing Scrub'},
        {v:'NO_ID_CARD',              l:'Not Wearing ID Card'},
        {v:'LATE_UPDATE',             l:'Late Record Update'},
        {v:'CUSTOM',                  l:'Custom Deduction'},
    ]
};

function selectType(type, el) {
    document.getElementById('adj_type').value = type;
    document.querySelectorAll('.type-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    var sel = document.getElementById('code_sel');
    sel.innerHTML = '<option value="">— Select —</option>';
    (allCodes[type] || []).forEach(function(item){
        sel.innerHTML += '<option value="'+item.v+'">'+item.l+'</option>';
    });
    document.getElementById('fields-section').style.display = '';
}

// re-populate on page load if old value exists
(function(){
    var t = document.getElementById('adj_type').value;
    if (t) {
        var el = document.querySelector('.type-card.'+t);
        if (el) selectType(t, el);
        var oldCode = '{{ old("code") }}';
        if (oldCode) {
            document.getElementById('code_sel').value = oldCode;
        }
    }
})();
</script>
@endpush
