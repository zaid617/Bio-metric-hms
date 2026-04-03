@extends('layouts.app')

@section('title')
    Payroll — {{ $payroll->employee->name ?? '#'.$payroll->id }}
@endsection

@push('css')
<style>
    .payslip-header { background: linear-gradient(135deg,#0d6efd,#0a58ca); color:#fff; border-radius:8px 8px 0 0; }
    .breakdown-row  { display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid rgba(0,0,0,.07); }
    .breakdown-row:last-child { border-bottom:none; }
    .breakdown-label { font-weight:500; }
    .earn  { color:#198754; }
    .deduct{ color:#dc3545; }
    .net-box { background:#d1e7dd;border-radius:8px;padding:18px 20px;border-left:5px solid #198754; }
    .info-pill { display:inline-block;padding:3px 10px;border-radius:20px;font-size:.78rem;background:rgba(255,255,255,.18); }
</style>
@endpush

@section('content')
<x-page-title title="Payroll Details"
    subtitle="{{ $payroll->employee->name ?? '' }} — {{ \Carbon\Carbon::create($payroll->year, $payroll->month)->format('F Y') }}" />

<div class="row">
    <div class="col-xl-10 mx-auto">

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

        {{-- Header Card --}}
        <div class="card mb-3 overflow-hidden">
            <div class="payslip-header p-4">
                <div class="row align-items-start">
                    <div class="col-md-7">
                        <h4 class="mb-1">{{ $payroll->employee->name ?? 'Employee' }}</h4>
                        <div class="mb-2">
                            <span class="info-pill">{{ $payroll->employee->designation ?? 'N/A' }}</span>
                            <span class="info-pill ms-1">{{ $payroll->branch->name ?? 'N/A' }}</span>
                        </div>
                        <small class="opacity-75">Employee ID: #{{ $payroll->employee_id }}</small>
                    </div>
                    <div class="col-md-5 text-md-end mt-3 mt-md-0">
                        <div class="fs-5 fw-bold">{{ \Carbon\Carbon::create($payroll->year, $payroll->month)->format('F Y') }}</div>
                        <div class="small opacity-75 mb-2">
                            @if($payroll->payroll_period_start && $payroll->payroll_period_end)
                                {{ $payroll->payroll_period_start->format('d M') }} – {{ $payroll->payroll_period_end->format('d M Y') }}
                            @endif
                        </div>
                        <span class="badge fs-6
                            @if($payroll->status=='paid') bg-secondary
                            @elseif($payroll->status=='approved') bg-success
                            @elseif($payroll->status=='reviewed') bg-info
                            @else bg-warning text-dark
                            @endif">{{ ucfirst($payroll->status ?? 'draft') }}</span>
                    </div>
                </div>
            </div>
            <div class="row g-0 text-center border-top">
                @foreach([
                    ['label'=>'Working Days','value'=> $payroll->total_working_days ?? 0,'icon'=>'calendar_today','color'=>'primary'],
                    ['label'=>'Present','value'=> $payroll->present_days ?? 0,'icon'=>'check_circle','color'=>'success'],
                    ['label'=>'Absent','value'=> $payroll->absent_days ?? 0,'icon'=>'cancel','color'=>'danger'],
                    ['label'=>'Late','value'=> $payroll->late_days ?? 0,'icon'=>'schedule','color'=>'warning'],
                    ['label'=>'Work Hours','value'=> number_format($payroll->total_working_hours ?? 0,1).'h','icon'=>'timer','color'=>'info'],
                    ['label'=>'Overtime','value'=> number_format($payroll->overtime_hours ?? 0,1).'h','icon'=>'more_time','color'=>'success'],
                ] as $att)
                <div class="col border-end py-3">
                    <div class="text-{{ $att['color'] }}"><span class="material-icons-outlined" style="font-size:20px">{{ $att['icon'] }}</span></div>
                    <div class="fw-bold">{{ $att['value'] }}</div>
                    <div class="text-muted" style="font-size:.72rem">{{ $att['label'] }}</div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- 3-column breakdown --}}
        <div class="row g-3 mb-3">
            {{-- Earnings --}}
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header bg-success bg-opacity-10">
                        <h6 class="mb-0 text-success"><span class="material-icons-outlined me-1" style="vertical-align:middle;font-size:18px">trending_up</span>Earnings</h6>
                    </div>
                    <div class="card-body p-3">
                        @php
                            $earningsItems = [
                                ['label'=>'Basic Salary',   'val'=> $payroll->basic_salary ?? $payroll->base_salary ?? 0],
                                ['label'=>'Additional Salary','val'=> $payroll->additional_salary ?? 0],
                                ['label'=>'Sunday Roster Incentive','val'=> data_get(collect($payroll->earnings_breakdown ?? [])->firstWhere('type', 'INCENTIVE_SUNDAY_ROSTER'), 'amount', 0)],
                                ['label'=>'Home Visit Incentive','val'=> data_get(collect($payroll->earnings_breakdown ?? [])->firstWhere('type', 'INCENTIVE_HOME_VISIT'), 'amount', 0)],
                                ['label'=>'Speech Therapy Incentive','val'=> data_get(collect($payroll->earnings_breakdown ?? [])->firstWhere('type', 'INCENTIVE_SPEECH_THERAPY'), 'amount', 0)],
                                ['label'=>'Dry Needling Incentive','val'=> data_get(collect($payroll->earnings_breakdown ?? [])->firstWhere('type', 'INCENTIVE_DRY_NEEDLING'), 'amount', 0)],
                                ['label'=>'Allied Health Council Allowance','val'=> data_get(collect($payroll->earnings_breakdown ?? [])->firstWhere('type', 'ALLOWANCE_ALLIED_HEALTH_COUNCIL'), 'amount', 0)],
                                ['label'=>'House Job Allowance','val'=> data_get(collect($payroll->earnings_breakdown ?? [])->firstWhere('type', 'ALLOWANCE_HOUSE_JOB'), 'amount', 0)],
                                ['label'=>'Conveyance Allowance','val'=> data_get(collect($payroll->earnings_breakdown ?? [])->firstWhere('type', 'ALLOWANCE_CONVEYANCE'), 'amount', 0)],
                                ['label'=>'Medical Allowance','val'=> data_get(collect($payroll->earnings_breakdown ?? [])->firstWhere('type', 'ALLOWANCE_MEDICAL'), 'amount', 0)],
                                ['label'=>'House Rent Allowance','val'=> data_get(collect($payroll->earnings_breakdown ?? [])->firstWhere('type', 'ALLOWANCE_HOUSE_RENT'), 'amount', 0)],
                                ['label'=>'Other Allowance','val'=> data_get(collect($payroll->earnings_breakdown ?? [])->firstWhere('type', 'OTHER_ALLOWANCE'), 'amount', 0)],
                                ['label'=>'Overtime Pay',   'val'=> $payroll->overtime ?? $payroll->overtime_pay ?? 0],
                                ['label'=>'Satisfactory Sessions','val'=> $payroll->satisfactory_sessions ?? 0],
                                ['label'=>'Treatment Extension Commission','val'=> $payroll->treatment_extension_commission ?? 0],
                                ['label'=>'Satisfaction Bonus','val'=> $payroll->satisfaction_bonus ?? 0],
                                ['label'=>'Assessment Bonus','val'=> $payroll->assessment_bonus ?? 0],
                                ['label'=>'Reference Bonus','val'=> $payroll->reference_bonus ?? 0],
                                ['label'=>'Personal Patient Commission','val'=> $payroll->personal_patient_commission ?? 0],
                            ];
                            $earningsTotal = !empty($payroll->earnings_breakdown)
                                ? collect($payroll->earnings_breakdown)->sum(fn ($item) => (float) ($item['amount'] ?? 0))
                                : collect($earningsItems)->sum('val');
                        @endphp
                        @foreach($earningsItems as $item)
                            @if((float)$item['val'] > 0)
                            <div class="breakdown-row">
                                <span class="breakdown-label small">{{ $item['label'] }}</span>
                                <span class="earn small">PKR {{ number_format($item['val'],0) }}</span>
                            </div>
                            @endif
                        @endforeach
                        @if(!empty($payroll->earnings_breakdown))
                            @foreach($payroll->earnings_breakdown as $item)
                                @if(isset($item['type']) && !in_array($item['type'],['BASIC_SALARY','ADDITIONAL_SALARY','INCENTIVE_SUNDAY_ROSTER','INCENTIVE_HOME_VISIT','INCENTIVE_SPEECH_THERAPY','INCENTIVE_DRY_NEEDLING','ALLOWANCE_ALLIED_HEALTH_COUNCIL','ALLOWANCE_HOUSE_JOB','ALLOWANCE_CONVEYANCE','ALLOWANCE_MEDICAL','ALLOWANCE_HOUSE_RENT','OTHER_ALLOWANCE','OVERTIME','SATISFACTORY_SESSIONS','TREATMENT_EXTENSION_COMMISSION','SATISFACTION_BONUS','ASSESSMENT_BONUS','REFERENCE_BONUS','PERSONAL_PATIENT_COMMISSION']) && (float)($item['amount']??0)>0)
                                <div class="breakdown-row">
                                    <span class="breakdown-label small text-primary">{{ str_replace('_',' ',$item['type']) }}</span>
                                    <span class="earn small">PKR {{ number_format($item['amount']??0,0) }}</span>
                                </div>
                                @endif
                            @endforeach
                        @endif
                        <div class="mt-2 pt-2 border-top d-flex justify-content-between fw-bold">
                            <span>Total Earnings</span><span class="earn">PKR {{ number_format($earningsTotal,0) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Awards --}}
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header bg-warning bg-opacity-10">
                        <h6 class="mb-0 text-warning"><span class="material-icons-outlined me-1" style="vertical-align:middle;font-size:18px">emoji_events</span>Awards</h6>
                    </div>
                    <div class="card-body p-3">
                        @if(!empty($payroll->awards_breakdown))
                            @foreach($payroll->awards_breakdown as $item)
                            <div class="breakdown-row">
                                <span class="small breakdown-label">{{ str_replace('_',' ',$item['type']??'Award') }}</span>
                                <span class="earn small">PKR {{ number_format($item['amount']??0,0) }}</span>
                            </div>
                            @endforeach
                        @else
                            <div class="text-muted small py-2">No awards this period.</div>
                        @endif
                        <div class="mt-2 pt-2 border-top d-flex justify-content-between fw-bold">
                            <span>Total Awards</span><span class="earn">PKR {{ number_format($payroll->awards_total ?? $payroll->bonus ?? 0,0) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Deductions --}}
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header bg-danger bg-opacity-10">
                        <h6 class="mb-0 text-danger"><span class="material-icons-outlined me-1" style="vertical-align:middle;font-size:18px">remove_circle</span>Deductions</h6>
                    </div>
                    <div class="card-body p-3">
                        @if(!empty($payroll->deductions_breakdown))
                            @foreach($payroll->deductions_breakdown as $item)
                            <div class="breakdown-row">
                                <span class="small breakdown-label">{{ str_replace('_',' ',$item['type']??'Deduction') }}</span>
                                <span class="deduct small">- PKR {{ number_format($item['amount']??0,0) }}</span>
                            </div>
                            @endforeach
                        @else
                            <div class="text-muted small py-2">No deductions this period.</div>
                        @endif
                        @if(($payroll->admin_adjustment_amount??0)!=0)
                        <div class="breakdown-row">
                            <span class="small breakdown-label">Admin Adjustment</span>
                            <span class="{{ $payroll->admin_adjustment_amount>0?'earn':'deduct' }} small">
                                {{ $payroll->admin_adjustment_amount>0?'+':'' }}PKR {{ number_format($payroll->admin_adjustment_amount,0) }}
                            </span>
                        </div>
                        @endif
                        <div class="mt-2 pt-2 border-top d-flex justify-content-between fw-bold">
                            <span>Total Deductions</span><span class="deduct">- PKR {{ number_format($payroll->deductions_total ?? $payroll->deductions ?? 0,0) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Net Salary Box --}}
        <div class="card mb-3">
            <div class="card-body">
                <div class="net-box d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Formula: (Basic + Additional + Allowances + Incentives + Other + Overtime + Awards) − Deductions</div>
                    </div>
                    <div class="text-end">
                        <div class="text-muted small">NET SALARY</div>
                        <div class="fs-3 fw-bold text-success">PKR {{ number_format($payroll->final_salary ?? $payroll->final_settlement ?? 0,2) }}</div>
                    </div>
                </div>
                @if($payroll->admin_adjustment_note)
                <div class="alert alert-info mt-3 mb-0">
                    <strong>Admin Note:</strong> {{ $payroll->admin_adjustment_note }}
                </div>
                @endif
            </div>
        </div>

        {{-- Linked Adjustments --}}
        @if($payroll->adjustments && $payroll->adjustments->count()>0)
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Linked Adjustments ({{ $payroll->adjustments->count() }})</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr><th>Type</th><th>Title / Code</th><th>Amount</th><th>Notes</th><th>By</th></tr>
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

        {{-- Actions --}}
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <a href="{{ route('attendance.payroll.index') }}" class="btn btn-outline-secondary">
                        <span class="material-icons-outlined me-1" style="vertical-align:middle;font-size:18px">arrow_back</span>Back to List
                    </a>
                    <div class="d-flex flex-wrap gap-2">
                        @if($payroll->status=='draft')
                            <a href="{{ route('attendance.payroll.edit',$payroll->id) }}" class="btn btn-warning">
                                <span class="material-icons-outlined me-1" style="vertical-align:middle;font-size:18px">tune</span>Add Adjustment
                            </a>
                            <form action="{{ route('attendance.payroll.approve',$payroll->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-success" onclick="return confirm('Approve this payroll?')">
                                    <span class="material-icons-outlined me-1" style="vertical-align:middle;font-size:18px">check_circle</span>Approve
                                </button>
                            </form>
                            <form action="{{ route('attendance.payroll.regenerate',$payroll->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-outline-primary" onclick="return confirm('Recalculate this payroll?')">
                                    <span class="material-icons-outlined me-1" style="vertical-align:middle;font-size:18px">refresh</span>Recalculate
                                </button>
                            </form>
                        @endif
                        @if($payroll->status=='approved')
                            <form action="{{ route('attendance.payroll.mark-paid',$payroll->id) }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="payment_method" value="cash">
                                <button class="btn btn-primary" onclick="return confirm('Mark as paid?')">
                                    <span class="material-icons-outlined me-1" style="vertical-align:middle;font-size:18px">payments</span>Mark as Paid
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
