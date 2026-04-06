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
        @foreach((array) data_get($payroll->payslip_data, 'warnings', []) as $warning)
            <div class="alert alert-warning">{{ $warning }}</div>
        @endforeach

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
                    ['label'=>'Late Minutes','value'=> ($payroll->total_late_minutes ?? 0).' min','icon'=>'alarm','color'=>'danger'],
                    ['label'=>'Work Hours','value'=> number_format($payroll->total_working_hours ?? 0,1).'h','icon'=>'timer','color'=>'info'],
                    ['label'=>'OT (Record Only)','value'=> number_format($payroll->total_overtime_hours ?? $payroll->overtime_hours ?? 0,1).'h','icon'=>'more_time','color'=>'secondary'],
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
                                $earningsRows = collect($payroll->earnings_breakdown ?? [])
                                    ->filter(fn ($item) => (float) ($item['amount'] ?? 0) > 0)
                                    ->values();
                                $earningsTotal = (float) $earningsRows->sum(fn ($item) => (float) ($item['amount'] ?? 0));
                            @endphp
                            @forelse($earningsRows as $item)
                                <div class="breakdown-row">
                                    <div class="pe-2">
                                        <div class="breakdown-label small">{{ str_replace('_',' ',(string)($item['type'] ?? 'Earning')) }}</div>
                                        @if(!empty($item['notes']))
                                            <div class="small text-muted">{{ $item['notes'] }}</div>
                                        @endif
                                    </div>
                                    <span class="earn small text-end">PKR {{ number_format((float) ($item['amount'] ?? 0),2) }}</span>
                                </div>
                            @empty
                                <div class="text-muted small py-2">No earnings this period.</div>
                            @endforelse
                        <div class="mt-2 pt-2 border-top d-flex justify-content-between fw-bold">
                            <span>Total Earnings</span><span class="earn">PKR {{ number_format((float) $earningsTotal,2) }}</span>
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
                        @php
                            $awardsRows = collect($payroll->awards_breakdown ?? [])
                                ->filter(fn ($item) => (float) ($item['amount'] ?? 0) > 0)
                                ->values();
                        @endphp
                        @forelse($awardsRows as $item)
                            <div class="breakdown-row">
                                <div class="pe-2">
                                    <div class="small breakdown-label">{{ str_replace('_',' ',(string)($item['type'] ?? 'Award')) }}</div>
                                    @if(!empty($item['notes']))
                                        <div class="small text-muted">{{ $item['notes'] }}</div>
                                    @endif
                                </div>
                                <span class="earn small text-end">PKR {{ number_format((float) ($item['amount'] ?? 0),2) }}</span>
                            </div>
                        @empty
                            <div class="text-muted small py-2">No awards this period.</div>
                        @endforelse
                        <div class="mt-2 pt-2 border-top d-flex justify-content-between fw-bold">
                            <span>Total Awards</span><span class="earn">PKR {{ number_format((float) ($payroll->awards_total ?? $payroll->bonus ?? 0),2) }}</span>
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
                        @php
                            $deductionRows = collect($payroll->deductions_breakdown ?? [])
                                ->filter(fn ($item) => (float) ($item['amount'] ?? 0) > 0)
                                ->values();
                        @endphp
                        @forelse($deductionRows as $item)
                            <div class="breakdown-row">
                                <div class="pe-2">
                                    <div class="small breakdown-label">{{ str_replace('_',' ',(string)($item['type'] ?? 'Deduction')) }}</div>
                                    @if(!empty($item['notes']))
                                        <div class="small text-muted">{{ $item['notes'] }}</div>
                                    @endif
                                </div>
                                <span class="deduct small text-end">- PKR {{ number_format((float) ($item['amount'] ?? 0),2) }}</span>
                            </div>
                        @empty
                            <div class="text-muted small py-2">No deductions this period.</div>
                        @endforelse
                        @if(($payroll->admin_adjustment_amount??0)!=0)
                        <div class="breakdown-row">
                            <span class="small breakdown-label">Admin Adjustment</span>
                            <span class="{{ $payroll->admin_adjustment_amount>0?'earn':'deduct' }} small">
                                {{ $payroll->admin_adjustment_amount>0?'+':'' }}PKR {{ number_format((float) $payroll->admin_adjustment_amount,2) }}
                            </span>
                        </div>
                        @endif
                        <div class="mt-2 pt-2 border-top d-flex justify-content-between fw-bold">
                            <span>Total Deductions</span><span class="deduct">- PKR {{ number_format((float) ($payroll->deductions_total ?? $payroll->deductions ?? 0),2) }}</span>
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
                        <div class="text-muted small">Formula: (Basic + Allowances + Incentives + Other + Awards/Bonus) - Deductions</div>
                        <div class="small fst-italic text-secondary">Overtime is tracked for records only and excluded from salary settlement.</div>
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
                                {{ $adj->adjustment_type=='deduction'?'-':'+' }} PKR {{ number_format((float) $adj->amount,2) }}
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
                        <a href="{{ route('payroll.payslip.preview',$payroll->id) }}" class="btn btn-outline-secondary" target="_blank">
                            <span class="material-icons-outlined me-1" style="vertical-align:middle;font-size:18px">article</span>Payslip Preview
                        </a>
                        <a href="{{ route('payroll.payslip.download',$payroll->id) }}" class="btn btn-outline-dark">
                            <span class="material-icons-outlined me-1" style="vertical-align:middle;font-size:18px">download</span>Download Payslip
                        </a>
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
