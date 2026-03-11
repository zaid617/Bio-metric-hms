@extends('layouts.app')

@section('title') Edit Adjustment #{{ $adjustment->id }} @endsection

@push('css')
<style>
    .type-card { cursor:pointer; border:2px solid transparent; transition:.15s; }
    .type-card.selected.earning   { border-color:#198754!important; background:#f0faf4; }
    .type-card.selected.award     { border-color:#ffc107!important; background:#fffdf0; }
    .type-card.selected.deduction { border-color:#dc3545!important; background:#fff5f5; }
</style>
@endpush

@section('content')
<x-page-title title="Edit Adjustment #{{ $adjustment->id }}"
    subtitle="{{ $adjustment->employee->name ?? '' }} — {{ \Carbon\Carbon::create($adjustment->year,$adjustment->month)->format('F Y') }}" />

<div class="row">
    <div class="col-xl-8 mx-auto">

        @if($adjustment->payroll_id)
        <div class="alert alert-warning d-flex align-items-start gap-3">
            <span class="material-icons-outlined mt-1">warning</span>
            <div>
                <strong>This adjustment has been linked to Payroll #{{ $adjustment->payroll_id }}.</strong><br>
                Editing it will NOT automatically recalculate the payroll. You may need to regenerate the payroll for the changes to take effect.
            </div>
        </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        <form action="{{ route('attendance.payroll.adjustments.update',$adjustment->id) }}" method="POST">
            @csrf @method('PUT')

            {{-- Employee + Period (read-only if linked) --}}
            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0">Employee & Period</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Employee <span class="text-danger">*</span></label>
                            <select name="employee_id" class="form-select" required {{ $adjustment->payroll_id ? 'disabled' : '' }}>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}" {{ old('employee_id',$adjustment->employee_id)==$emp->id ? 'selected' : '' }}>
                                        {{ $emp->name }} @if($emp->employee_id)({{ $emp->employee_id }})@endif
                                    </option>
                                @endforeach
                            </select>
                            @if($adjustment->payroll_id)
                                <input type="hidden" name="employee_id" value="{{ $adjustment->employee_id }}">
                            @endif
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Month</label>
                            <select name="month" class="form-select" {{ $adjustment->payroll_id ? 'disabled' : '' }}>
                                @for($m=1;$m<=12;$m++)
                                    <option value="{{ $m }}" {{ old('month',$adjustment->month)==$m ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::create(null,$m)->format('F') }}
                                    </option>
                                @endfor
                            </select>
                            @if($adjustment->payroll_id)<input type="hidden" name="month" value="{{ $adjustment->month }}">@endif
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Year</label>
                            <input type="number" name="year" class="form-control"
                                   value="{{ old('year',$adjustment->year) }}" min="2020" max="2100"
                                   {{ $adjustment->payroll_id ? 'disabled' : '' }}>
                            @if($adjustment->payroll_id)<input type="hidden" name="year" value="{{ $adjustment->year }}">@endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Type --}}
            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0">Adjustment Type</h6></div>
                <div class="card-body">
                    <input type="hidden" name="adjustment_type" id="adj_type" value="{{ old('adjustment_type',$adjustment->adjustment_type) }}">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="type-card earning card text-center p-3 {{ (old('adjustment_type',$adjustment->adjustment_type)=='earning'?'selected':'' ) }}"
                                 onclick="selectType('earning',this)">
                                <span class="material-icons-outlined text-success mb-2" style="font-size:32px">add_circle_outline</span>
                                <div class="fw-bold text-success">Earning</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="type-card award card text-center p-3 {{ (old('adjustment_type',$adjustment->adjustment_type)=='award'?'selected':'') }}"
                                 onclick="selectType('award',this)">
                                <span class="material-icons-outlined text-warning mb-2" style="font-size:32px">emoji_events</span>
                                <div class="fw-bold text-warning">Award</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="type-card deduction card text-center p-3 {{ (old('adjustment_type',$adjustment->adjustment_type)=='deduction'?'selected':'') }}"
                                 onclick="selectType('deduction',this)">
                                <span class="material-icons-outlined text-danger mb-2" style="font-size:32px">remove_circle_outline</span>
                                <div class="fw-bold text-danger">Deduction</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Details --}}
            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0">Adjustment Details</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Code / Category <span class="text-danger">*</span></label>
                            <select name="code" id="code_sel" class="form-select" required>
                                <option value="">— Select —</option>
                            </select>
                            @error('code')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Amount (PKR) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">PKR</span>
                                <input type="number" name="amount" step="0.01" min="0.01" class="form-control"
                                       value="{{ old('amount',$adjustment->amount) }}" required>
                            </div>
                            @error('amount')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Title / Label</label>
                            <input type="text" name="title" class="form-control"
                                   value="{{ old('title',$adjustment->title) }}" maxlength="191">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Notes / Reason</label>
                            <textarea name="notes" class="form-control" rows="3">{{ old('notes',$adjustment->notes ?? $adjustment->reason) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-end">
                <a href="{{ route('attendance.payroll.adjustments.index') }}" class="btn btn-outline-secondary">Cancel</a>
                @if($adjustment->payroll_id)
                    <a href="{{ route('attendance.payroll.show',$adjustment->payroll_id) }}" class="btn btn-outline-primary">
                        View Payroll #{{ $adjustment->payroll_id }}
                    </a>
                @endif
                <button type="submit" class="btn btn-primary px-4">
                    <span class="material-icons-outlined me-1" style="vertical-align:middle;font-size:18px">save</span> Update Adjustment
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
        {v:'ADDITIONAL_SALARY',              l:'Additional Salary'},
        {v:'OVERTIME',                        l:'Overtime Payment'},
        {v:'TREATMENT_EXTENSION_COMMISSION',  l:'Treatment Extension Commission (10%)'},
        {v:'SATISFACTION_BONUS',              l:'Patient Satisfaction Bonus'},
        {v:'ASSESSMENT_BONUS',                l:'Staff Assessment Incentive (5%)'},
        {v:'REFERENCE_BONUS',                 l:'Patient Reference Reward'},
        {v:'PERSONAL_PATIENT_COMMISSION',     l:'Personal Patient Commission (20%)'},
        {v:'CUSTOM',                          l:'Custom Earning'},
    ],
    award: [
        {v:'PUNCTUALITY_AWARD', l:'Punctuality Award (PKR 2,000)'},
        {v:'CUSTOM',            l:'Custom Award'},
    ],
    deduction: [
        {v:'SESSION_NUMBER_MISSING',   l:'Missing Session Number'},
        {v:'WRONG_EMR_NUMBER',         l:'Wrong EMR Number'},
        {v:'TIME_MISSING',             l:'Missing Time Entry'},
        {v:'WRONG_PATIENT_NAME',       l:'Wrong Patient Name'},
        {v:'ABSENT',                   l:'Absence Deduction'},
        {v:'LATE_COMING',              l:'Late Coming Fine'},
        {v:'ADVANCE_SALARY_DEDUCTION', l:'Advance Salary Deduction'},
        {v:'NO_SCRUB',                 l:'Not Wearing Scrub'},
        {v:'NO_ID_CARD',               l:'Not Wearing ID Card'},
        {v:'LATE_UPDATE',              l:'Late Record Update'},
        {v:'CUSTOM',                   l:'Custom Deduction'},
    ]
};

var currentCode = '{{ $adjustment->code }}';

function selectType(type, el) {
    document.getElementById('adj_type').value = type;
    document.querySelectorAll('.type-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    var sel = document.getElementById('code_sel');
    sel.innerHTML = '<option value="">— Select —</option>';
    (allCodes[type] || []).forEach(function(item){
        var selected = (item.v === currentCode) ? ' selected' : '';
        sel.innerHTML += '<option value="'+item.v+'"'+selected+'>'+item.l+'</option>';
    });
}

(function(){
    var t = document.getElementById('adj_type').value;
    if (t) {
        var el = document.querySelector('.type-card.'+t);
        if (el) selectType(t, el);
    }
})();
</script>
@endpush
